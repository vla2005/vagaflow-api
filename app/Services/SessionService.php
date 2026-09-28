<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionService
{
    /** @param array{name: string, email: string, password: string} $input */
    public function register(array $input): User
    {
        $user = User::query()->create([
            'name' => trim($input['name']),
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        Auth::guard('web')->login($user);

        return $user;
    }

    /** @param array{email: string, password: string} $credentials */
    public function authenticate(array $credentials): User
    {
        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }

        $user = Auth::guard('web')->user();

        if (! $user instanceof User) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }

        return $user;
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();
        Auth::guard('sanctum')->forgetUser();
    }
}
