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
     * Global feed — newest posts first.
     *
     * Eager loads `user` here, at the point the query is constructed,
     * guaranteeing N+1 protection regardless of how Lighthouse resolves.
     * Lighthouse applies pagination on top of this builder.
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