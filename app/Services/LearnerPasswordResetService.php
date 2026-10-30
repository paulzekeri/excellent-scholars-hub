<?php

namespace App\Services;

use App\Mail\LearnerPasswordResetMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Password resets for a child who has no mailbox (note 12).
 *
 * The standard Laravel broker is keyed by email, which cannot work when two
 * siblings share one guardian address. This service stores a hashed token under
 * a per-user key (`noemail:{id}`) and emails the one-time link to the guardian,
 * so each child's link stays independent.
 */
class LearnerPasswordResetService
{
    /** The row key used in `password_resets` for this user. */
    public function keyFor(User $user): string
    {
        return 'noemail:' . $user->id;
    }

    /** The guardian's address that receives the link. */
    public function guardianEmail(User $user): ?string
    {
        return optional(optional(optional($user->learner)->parent)->user)->email;
    }

    /** True when this account needs the guardian fallback (no own email). */
    public function handles(User $user): bool
    {
        return empty($user->email) && (bool) $this->guardianEmail($user);
    }

    /**
     * Create a single-use token for this user and return the plain value.
     * Callers either email it (send()) or hand it over (admin fallback).
     */
    public function createToken(User $user): string
    {
        $plain = Str::random(64);

        DB::table('password_resets')->where('email', $this->keyFor($user))->delete();
        DB::table('password_resets')->insert([
            'email' => $this->keyFor($user),
            'token' => Hash::make($plain),
            'created_at' => now(),
        ]);

        return $plain;
    }

    /** The one-time URL the guardian opens. */
    public function resetUrl(User $user, string $plainToken): string
    {
        return route('password.reset', ['token' => $plainToken, 'uid' => $user->id]);
    }

    /**
     * Create a single-use token and email it. A caller may override the
     * destination (the parent dashboard sends it to the signed-in guardian).
     */
    public function send(User $user, ?string $destination = null): bool
    {
        $destination = $destination ?: $this->guardianEmail($user);

        if (! $destination) {
            return false;
        }

        $plain = $this->createToken($user);

        try {
            Mail::to($destination)->send(new LearnerPasswordResetMail(
                $user->name,
                $this->resetUrl($user, $plain),
                (int) config('auth.passwords.users.expire', 60)
            ));
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    /** Check a plain token against the stored hash and the expiry window. */
    public function tokenIsValid(User $user, string $plain): bool
    {
        $row = DB::table('password_resets')->where('email', $this->keyFor($user))->first();

        if (! $row) {
            return false;
        }

        $expires = (int) config('auth.passwords.users.expire', 60);

        if ($row->created_at && Carbon::parse($row->created_at)->lt(now()->subMinutes($expires))) {
            $this->forget($user);

            return false;
        }

        return Hash::check($plain, $row->token);
    }

    /** Remove the token once it has been used (or has expired). */
    public function forget(User $user): void
    {
        DB::table('password_resets')->where('email', $this->keyFor($user))->delete();
    }
}
