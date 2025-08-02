<?php

namespace Modules\User\app\Repositories;

use Modules\User\app\Models\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;
    public function findByEmail(string $email): ?User;
}
