<?php

declare(strict_types=1);

namespace App\GraphQL\Exceptions;

use GraphQL\Error\ClientAware;
use GraphQL\Error\ProvidesExtensions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GraphQLExceptionHandler
{
    /**
     * Lighthouse v6.66 has a bug where ClientAware exceptions 
     * enter a fatal loop
     * 
     * @see https://github.com/nuwave/lighthouse/issues — v6.66 bug
     */
    public function handle(ClientAware $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('graphql')) {
            return null;
        }

        return response()->json([
            'errors' => [
                [
                    'message'    => $exception->getMessage(),
                    'extensions' => $exception instanceof ProvidesExtensions
                        ? $exception->getExtensions()
                        : [],
                ],
            ],
        ], 200);
    }
}