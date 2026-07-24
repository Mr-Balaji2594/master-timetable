<?php

namespace App\Auth;

use App\Models\Employee;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class EmployeeUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return Employee::where('id', $identifier)->where('is_active', true)->first();
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (!isset($credentials['emp_id'])) {
            return null;
        }

        return Employee::where('emp_id', $credentials['emp_id'])
            ->where('is_active', true)
            ->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (!isset($credentials['password'])) {
            return false;
        }

        return password_verify($credentials['password'], $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (!isset($credentials['password'])) {
            return;
        }

        if (password_needs_rehash($user->getAuthPassword(), PASSWORD_BCRYPT, ['cost' => 12])) {
            $user->password = bcrypt($credentials['password']);
            $user->save();
        }
    }
}
