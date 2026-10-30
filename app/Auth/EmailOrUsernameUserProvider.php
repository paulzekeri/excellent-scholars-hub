<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Str;

/**
 * Lets one login field accept either an email address or a username.
 *
 * Laravel's login, "remember me", throttling and password broker all keep
 * working; only the credential lookup changes. The broker still passes an
 * `email` credential, which falls through to the normal `where email = ?`.
 */
class EmailOrUsernameUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        $credentials = array_filter(
            $credentials,
            function ($key) {
                return ! Str::contains($key, 'password');
            },
            ARRAY_FILTER_USE_KEY
        );

        unset($credentials['remember']);

        if (empty($credentials)) {
            return null;
        }

        $query = $this->newModelQuery();

        if (array_key_exists('login', $credentials)) {
            $login = trim((string) $credentials['login']);
            unset($credentials['login']);

            $query->where(function ($query) use ($login) {
                $query->where('email', $login)->orWhere('username', $login);
            });
        }

        foreach ($credentials as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }
}
