<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Actions\Post\CreatePostAction;
use App\Models\Post;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Exceptions\ValidationException as LighthouseValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CreatePost
{
    public function __construct(
        private readonly CreatePostAction $createPostAction
    ) {}

    /**
     * Thin resolver — delegates to the application action.
     *
     * Laravel's ValidationException must be explicitly converted to
     * Lighthouse's ValidationException here because only ClientAware
     * exceptions are intercepted by GraphQLExceptionHandler.
     * LaravelValidationException does not implement ClientAware.
     *
     * @param  array<string, mixed>  $args
     */
    public function __invoke(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): ?Post {
        try {
            return $this->createPostAction->execute(
                user: $context->user(),
                body: $args['body'],
            );
        } catch (ValidationException $e) {
            throw LighthouseValidationException::fromLaravel($e);
        }
    }
}