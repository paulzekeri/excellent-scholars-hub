<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Learner;
use App\Models\Notification;
use App\Models\ParentModel;
use App\Models\Payment;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * A parent can pay ahead for a class before the child is enrolled. The CEO
 * checks the money, verifies the payment and then adds the learner to the
 * class from the payment page.
 */
class PaymentEnrolmentTest extends TestCase
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

    private function makeClass(string $name = 'JSS2 Coding', ?int $max = null): Classes
    {
        $tutorUser = $this->makeUser('tutor', 'Tutor ' . $name);
        $tutor = Tutor::create(['user_id' => $tutorUser->id]);

        return Classes::create([
            'name' => $name,
            'code' => 'CLS-' . uniqid(),
            'tutor_id' => $tutor->id,
            'max_students' => $max,
            'is_active' => true,
        ]);
    }

    private function payAhead(Learner $child, User $payer, Classes $class, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'user_id' => $payer->id,
            'learner_id' => $child->id,
            'payment_type' => 'tuition',
            'term' => 'First Term',
            'class_id' => $class->id,
            'amount' => 50000,
            'total_fee' => 100000,
            'status' => Payment::STATUS_PENDING,
            'payment_date' => now(),
        ], $overrides));
    }

    public function test_the_ceo_sees_the_enrol_action_for_a_pay_ahead_payment(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $class = $this->makeClass();
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        $payment = $this->payAhead($child, $parentUser, $class);

        $this->actingAs($ceo)->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee('Paid ahead')
            ->assertSee('Add Chidi Okafor to JSS2 Coding')
            ->assertSee('admin/payments/' . $payment->id . '/enroll', false);

        // Paying ahead does not enrol the learner by itself.
        $this->assertFalse($class->learners()->where('learner_id', $child->id)->exists());
    }

    public function test_the_ceo_enrols_the_learner_after_verifying_the_payment(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $class = $this->makeClass();
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        $payment = $this->payAhead($child, $parentUser, $class);

        $this->actingAs($ceo)
            ->from(route('admin.payments.show', $payment))
            ->post(route('admin.payments.verify', $payment))
            ->assertRedirect(route('admin.payments.show', $payment));

        $this->actingAs($ceo)
            ->from(route('admin.payments.show', $payment))
            ->post(route('admin.payments.enroll', $payment))
            ->assertRedirect(route('admin.payments.show', $payment))
            ->assertSessionHas('success');

        $this->assertTrue($class->learners()->where('learner_id', $child->id)->exists());

        // The parent is told the child has been added.
        $this->assertSame(1, Notification::where('user_id', $parentUser->id)
            ->where('title', 'Added to JSS2 Coding')
            ->count());
    }

    public function test_enrolling_twice_is_refused(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $class = $this->makeClass();
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        $payment = $this->payAhead($child, $parentUser, $class);

        $this->actingAs($ceo)->post(route('admin.payments.enroll', $payment))->assertSessionHas('success');
        $this->actingAs($ceo)->post(route('admin.payments.enroll', $payment))->assertSessionHas('error');

        $this->assertSame(1, $class->learners()->where('learner_id', $child->id)->count());
    }

    public function test_a_full_class_is_never_overfilled(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $class = $this->makeClass('JSS2 Coding', 1);
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        // Somebody else already has the only place.
        $class->learners()->attach($this->makeFamily()[2]->id);

        $payment = $this->payAhead($child, $parentUser, $class);

        $this->actingAs($ceo)->get(route('admin.payments.show', $payment))
            ->assertOk()
            ->assertSee('This class is full');

        $this->actingAs($ceo)->post(route('admin.payments.enroll', $payment))->assertSessionHas('error');

        $this->assertFalse($class->learners()->where('learner_id', $child->id)->exists());
    }

    public function test_only_the_ceo_can_enrol_from_a_payment(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $class = $this->makeClass();
        $admin = $this->makeUser('admin', 'School Admin');

        $payment = $this->payAhead($child, $parentUser, $class);

        $this->actingAs($admin)->post(route('admin.payments.enroll', $payment))->assertForbidden();
        $this->actingAs($parentUser)->post(route('admin.payments.enroll', $payment))->assertForbidden();

        $this->assertFalse($class->learners()->where('learner_id', $child->id)->exists());
    }

    public function test_a_payment_without_a_class_has_nothing_to_enrol(): void
    {
        [$parentUser, , $child] = $this->makeFamily();
        $ceo = $this->makeUser('ceo', 'Chief Executive');

        $payment = Payment::create([
            'user_id' => $parentUser->id,
            'learner_id' => $child->id,
            'payment_type' => 'registration',
            'term' => 'First Term',
            'amount' => 15000,
            'status' => Payment::STATUS_PENDING,
            'payment_date' => now(),
        ]);

        $this->actingAs($ceo)->post(route('admin.payments.enroll', $payment))
            ->assertSessionHas('error');
    }
}
