<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Post;
use App\Models\User;

interface PostRepositoryInterface
{
    /**
     * Persist a new post for the given user.
     */
    public function create(User $user, string $body): Post;
}