<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Domain\Post\Rules\PostBodyRule;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreatePostAction
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository
    ) {}

    /**
     * @throws ValidationException if body fails validation
     */
    public function execute(User $user, string $body): Post
    {
        Validator::make(
            ['body' => $body],
            ['body' => ['required', 'string', new PostBodyRule()]]
        )->validate();

        return $this->postRepository->create($user, $body);
    }
}