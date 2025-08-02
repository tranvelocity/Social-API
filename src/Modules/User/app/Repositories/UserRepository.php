<?php

namespace Modules\User\app\Repositories;

use Modules\User\app\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    // Add other repository methods as needed
}
