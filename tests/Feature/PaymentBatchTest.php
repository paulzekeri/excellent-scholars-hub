<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Learner;
use App\Models\Notification;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A parent often clears two or three subjects in one bank transfer. The form
 * posts one row per payment line (child, type, class, amount, purpose) under the
 * same reference, so each class keeps its own fee and balance.
 */
class PaymentBatchTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $roleSlug, string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('slug', $roleSlug)->firstOrFail()->id,
        ]);
    }

    /**
     * @return array{0: User, 1: ParentModel, 2: Learner}
     */
    private function makeFamily(): array
    {
        $parentUser = $this->makeUser('parent', 'Ada Okafor');
        $parent = ParentModel::create(['user_id' => $parentUser->id]);

        $child = Learner::create([
            'user_id' => $this->makeUser('learner', 'Chidi Okafor')->id,
            'parent_id' => $parent->id,
            'grade_level' => 'JSS 2',
        ]);

        return [$parentUser, $parent, $child];
    }

    private function makeClass(string $name = 'JSS2 Mathematics'): Classes
    {
        $tutorUser = $this->makeUser('tutor', 'Tutor ' . $name);
        $tutor = Tutor::create(['user_id' => $tutorUser->id]);

        return Classes::create([
            'name' => $name,
            'code' => 'CLS-' . uniqid(),
            'tutor_id' => $tutor->id,
            'is_active' => true,
        ]);
    }

    private function account(): PaymentAccount
    {
        return PaymentAccount::firstOrCreate(
            ['account_number' => '0099887766'],
            [
                'bank_name' => 'GTBank',
                'account_name' => 'Excellent Scholars Academy',
                'is_active' => true,
            ]
        );
    }

    /**
     * Build the `lines` payload the form posts: one row per class.
     *
     * @param  array<int, float|int>  $amounts  keyed by class id
     * @param  array<int, float|int>  $fees     keyed by class id
     */
    private function batchData(Learner $child, array $amounts, array $fees = [], array $overrides = []): array
    {
        $lines = [];

        foreach ($amounts as $classId => $amount) {
            $lines[] = [
                'learner_id' => $child->id,
                'payment_type' => 'tuition',
                'class_id' => $classId,
                'amount' => $amount,
                'total_fee' => $fees[$classId] ?? null,
                'purpose' => null,
            ];
        }

        return array_merge([
            'term' => 'First Term',
            'payment_method' => Payment::METHOD_TRANSFER,
            'payment_account_id' => $this->account()->id,
            'reference' => 'TRF-' . uniqid(),
            'payment_date' => now()->toDateString(),
            'lines' => $lines,
        ], $overrides);
    }

    public function test_one_transfer_can_pay_for_two_classes(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $maths = $this->makeClass('JSS2 Mathematics');
        $english = $this->makeClass('JSS2 English');
        $child->classes()->attach([$maths->id, $english->id]);

        $this->actingAs($parentUser)
            ->post(route('parent.payments.store'), $this->batchData(
                $child,
                [$maths->id => 60000, $english->id => 40000],
                [$maths->id => 120000, $english->id => 80000]
            ))
            ->assertRedirect(route('parent.payments.index'))
            ->assertSessionHas('success', fn ($message) => str_contains($message, '2 installments'));

        $payments = Payment::where('learner_id', $child->id)->orderBy('id')->get();

        $this->assertCount(2, $payments);
        // Both rows carry the single bank reference the parent typed.
        $this->assertCount(1, $payments->pluck('reference')->unique());
        $this->assertSame([$maths->id, $english->id], $payments->pluck('class_id')->all());
        $this->assertSame([60000.0, 40000.0], $payments->pluck('amount')->all());
        $this->assertSame([120000.0, 80000.0], $payments->pluck('total_fee')->all());
        $this->assertSame([Payment::STATUS_PENDING, Payment::STATUS_PENDING], $payments->pluck('status')->all());
    }

    public function test_each_row_keeps_its_own_type_and_purpose(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $maths = $this->makeClass('JSS2 Mathematics');
        $coding = $this->makeClass('JSS2 Coding');

        $this->actingAs($parentUser)->post(route('parent.payments.store'), $this->batchData(
            $child,
            [$maths->id => 50000, $coding->id => 15000],
            [],
            [
                'lines' => [
                    [
                        'learner_id' => $child->id,
                        'payment_type' => 'tuition',
                        'class_id' => $maths->id,
                        'amount' => 50000,
                        'total_fee' => 100000,
                        'purpose' => 'First term tuition',
                    ],
                    [
                        'learner_id' => $child->id,
                        'payment_type' => 'registration',
                        'class_id' => $coding->id,
                        'amount' => 15000,
                        'purpose' => 'Registration for coding',
                    ],
                ],
            ]
        ))->assertRedirect(route('parent.payments.index'));

        $payments = Payment::where('learner_id', $child->id)->orderBy('id')->get();

        $this->assertCount(2, $payments);
        // One transfer, but each line says what it is and which class it pays.
        $this->assertSame(['tuition', 'registration'], $payments->pluck('payment_type')->all());
        $this->assertSame(['First term tuition', 'Registration for coding'], $payments->pluck('purpose')->all());
    }

    public function test_a_ticked_class_without_an_amount_is_skipped(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $maths = $this->makeClass();
        $english = $this->makeClass('JSS2 English');
        $child->classes()->attach([$maths->id, $english->id]);

        $this->actingAs($parentUser)
            ->post(route('parent.payments.store'), $this->batchData($child, [$maths->id => 30000, $english->id => 0]))
            ->assertRedirect(route('parent.payments.index'));

        $this->assertSame(1, Payment::where('learner_id', $child->id)->count());
        $this->assertSame($maths->id, Payment::where('learner_id', $child->id)->first()->class_id);
    }

    public function test_a_batch_needs_at_least_one_amount(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $maths = $this->makeClass();
        $child->classes()->attach($maths->id);

        $this->actingAs($parentUser)
            ->from(route('parent.payments.create'))
            ->post(route('parent.payments.store'), $this->batchData($child, [$maths->id => 0]))
            ->assertSessionHasErrors('lines');

        $this->assertSame(0, Payment::where('learner_id', $child->id)->count());
    }

    public function test_a_ticked_row_can_pay_ahead_for_a_class_the_child_has_not_started(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $own = $this->makeClass();
        $ahead = $this->makeClass('Another Learner Class');
        $child->classes()->attach($own->id);

        $this->actingAs($parentUser)
            ->post(route('parent.payments.store'), $this->batchData($child, [$ahead->id => 20000]))
            ->assertRedirect(route('parent.payments.index'));

        $payment = Payment::where('learner_id', $child->id)->firstOrFail();

        // The money is allocated to the chosen class, but the child is not
        // enrolled yet - the CEO adds them after confirming the payment.
        $this->assertSame($ahead->id, $payment->class_id);
        $this->assertFalse($child->classes()->where('class_id', $ahead->id)->exists());
    }

    public function test_a_batch_line_for_a_class_that_does_not_exist_is_rejected(): void
    {
        [$parentUser, , $child] = $this->makeFamily();

        $this->actingAs($parentUser)
            ->from(route('parent.payments.create'))
            ->post(route('parent.payments.store'), $this->batchData($child, [999999 => 20000]))
            ->assertSessionHasErrors('lines.0.class_id');

        $this->assertSame(0, Payment::where('learner_id', $child->id)->count());
    }

    public function test_a_row_records_the_class_the_parent_chooses(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $enrolled = $this->makeClass('JSS2 Mathematics');
        $ahead = $this->makeClass('JSS2 Coding');
        $child->classes()->attach($enrolled->id);

        $this->actingAs($parentUser)->post(route('parent.payments.store'), $this->batchData(
            $child,
            [$ahead->id => 30000],
            [$ahead->id => 90000]
        ))->assertRedirect(route('parent.payments.index'))->assertSessionHas('success');

        $payment = Payment::where('learner_id', $child->id)->firstOrFail();

        $this->assertSame($ahead->id, $payment->class_id);
        $this->assertSame(30000.0, $payment->amount);
        $this->assertSame(90000.0, $payment->total_fee);
        $this->assertFalse($child->classes()->where('class_id', $ahead->id)->exists());
    }

    public function test_the_form_offers_classes_the_child_has_not_started(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $maths = $this->makeClass('JSS2 Mathematics');
        $coding = $this->makeClass('JSS2 Coding');
        $child->classes()->attach($maths->id);

        $this->actingAs($parentUser)->get(route('parent.payments.create'))
            ->assertOk()
            ->assertSee('Payments on this transfer')
            ->assertSee('JSS2 Coding')
            ->assertSee('name="lines[0][class_id]"', false)
            ->assertSee('name="lines[0][payment_type]"', false)
            ->assertSee('Add another payment');
    }

    public function test_a_batch_line_cannot_exceed_its_total_fee(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $maths = $this->makeClass();
        $child->classes()->attach($maths->id);

        $this->actingAs($parentUser)
            ->from(route('parent.payments.create'))
            ->post(route('parent.payments.store'), $this->batchData(
                $child,
                [$maths->id => 150000],
                [$maths->id => 100000]
            ))
            ->assertSessionHasErrors('lines.0.amount');

        $this->assertSame(0, Payment::where('learner_id', $child->id)->count());
    }

    public function test_the_receipt_is_shared_by_every_installment_in_the_batch(): void
    {
        Storage::fake('public');

        [$parentUser, , $child] = $this->makeFamily();
        $maths = $this->makeClass();
        $english = $this->makeClass('JSS2 English');
        $child->classes()->attach([$maths->id, $english->id]);

        $this->actingAs($parentUser)->post(route('parent.payments.store'), $this->batchData(
            $child,
            [$maths->id => 20000, $english->id => 20000],
            [],
            ['receipt' => UploadedFile::fake()->create('teller.pdf', 80, 'application/pdf')]
        ))->assertRedirect(route('parent.payments.index'));

        $paths = Payment::where('learner_id', $child->id)->pluck('receipt_path');

        $this->assertCount(2, $paths);
        $this->assertCount(1, $paths->unique());
        Storage::disk('public')->assertExists($paths->first());
    }

    public function test_the_ceo_is_told_once_about_the_whole_transfer(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $ceo = $this->makeUser('ceo', 'Chief Executive');
        $maths = $this->makeClass();
        $english = $this->makeClass('JSS2 English');
        $child->classes()->attach([$maths->id, $english->id]);

        $this->actingAs($parentUser)->post(route('parent.payments.store'), $this->batchData(
            $child,
            [$maths->id => 20000, $english->id => 20000]
        ))->assertRedirect(route('parent.payments.index'));

        $notes = Notification::where('user_id', $ceo->id)
            ->where('title', 'Payment awaiting verification')
            ->get();

        $this->assertCount(1, $notes);
        $this->assertStringContainsString('40,000.00', $notes->first()->message);
        $this->assertStringContainsString('2 installments', $notes->first()->message);
    }

    public function test_the_form_lists_the_classes_the_parent_can_pay_for(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $maths = $this->makeClass('JSS2 Mathematics');
        $child->classes()->attach($maths->id);

        $this->actingAs($parentUser)->get(route('parent.payments.create'))
            ->assertOk()
            ->assertSee('Payments on this transfer')
            ->assertSee('JSS2 Mathematics')
            ->assertSee('name="lines[0][class_id]"', false)
            ->assertSee('name="lines[0][learner_id]"', false)
            ->assertSee('This submission:');
    }

    /**
     * "If a parent has many children, record payment once for all children, not
     * per child" — one transfer, one reference, one receipt, two children.
     */
    public function test_one_transfer_can_cover_several_children(): void
    {
        [$parentUser, $parent, $child] = $this->makeFamily();

        $second = Learner::create([
            'user_id' => $this->makeUser('learner', 'Ada Okafor Jnr')->id,
            'parent_id' => $parent->id,
            'grade_level' => 'JSS 1',
        ]);

        $maths = $this->makeClass('JSS2 Mathematics');
        $english = $this->makeClass('JSS1 English');
        $child->classes()->attach($maths->id);
        $second->classes()->attach($english->id);

        $reference = 'FAM-' . uniqid();

        $this->actingAs($parentUser)->post(route('parent.payments.store'), [
            'term' => 'First Term',
            'payment_method' => Payment::METHOD_TRANSFER,
            'payment_account_id' => $this->account()->id,
            'reference' => $reference,
            'payment_date' => now()->toDateString(),
            'lines' => [
                [
                    'learner_id' => $child->id,
                    'payment_type' => 'tuition',
                    'class_id' => $maths->id,
                    'amount' => 20000,
                    'total_fee' => 100000,
                ],
                [
                    'learner_id' => $second->id,
                    'payment_type' => 'registration',
                    'class_id' => $english->id,
                    'amount' => 30000,
                    'total_fee' => 100000,
                ],
            ],
        ])->assertRedirect(route('parent.payments.index'))->assertSessionHas('success');

        $payments = Payment::where('reference', $reference)->get();

        $this->assertCount(2, $payments);
        $this->assertSame(
            [$child->id, $second->id],
            $payments->pluck('learner_id')->sort()->values()->all()
        );
        $this->assertSame(50000.0, (float) $payments->sum('amount'));
        $this->assertSame($maths->id, $payments->firstWhere('learner_id', $child->id)->class_id);
        $this->assertSame($english->id, $payments->firstWhere('learner_id', $second->id)->class_id);
    }

    public function test_a_row_for_another_family_is_rejected(): void
    {
        [$parentUser, , $child] = $this->makeFamily();

        $stranger = Learner::create([
            'user_id' => $this->makeUser('learner', 'Stranger Child')->id,
        ]);

        $maths = $this->makeClass();
        $child->classes()->attach($maths->id);

        $this->actingAs($parentUser)
            ->from(route('parent.payments.create'))
            ->post(route('parent.payments.store'), [
                'term' => 'First Term',
                'payment_method' => Payment::METHOD_TRANSFER,
                'payment_account_id' => $this->account()->id,
                'payment_date' => now()->toDateString(),
                'lines' => [[
                    'learner_id' => $stranger->id,
                    'payment_type' => 'tuition',
                    'class_id' => $maths->id,
                    'amount' => 10000,
                ]],
            ])
            ->assertSessionHasErrors('lines.0.learner_id');

        $this->assertSame(0, Payment::where('learner_id', $stranger->id)->count());
        $this->assertSame(0, Payment::where('learner_id', $child->id)->count());
    }
}
