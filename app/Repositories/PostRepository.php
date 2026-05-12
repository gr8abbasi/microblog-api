<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\PostRepositoryInterface;

class PostRepository implements PostRepositoryInterface
{
    /**
     * Create and persist a new post for the given user.
     */
    public function create(User $user, string $body): Post
    {
        return Post::create([
            'user_id' => $user->id,
            'body'    => $body,
        ]);
    }
}