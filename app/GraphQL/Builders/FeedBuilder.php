<?php

declare(strict_types=1);

namespace App\GraphQL\Builders;

use App\Models\Post;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Builder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class FeedBuilder
{
    /**
     * Global feed, newest posts first.
     *
     * @param  array<string, mixed>  $args
     */
    public function __invoke(
        mixed $root,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo
    ): Builder {
        return Post::query()
            ->with('user')
            ->latest();
    }
}