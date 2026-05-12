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
     * Handle ClientAware exceptions thrown during GraphQL execution.
     *
     * Lighthouse v6.66 has a bug where ClientAware exceptions
     * (ValidationException, AuthenticationException) enter a fatal loop
     * in Lighthouse's own error pipeline. This affects both Laravel 11.
     * We intercept them here before they reach that pipeline and return a
     * properly formatted GraphQL error response.
     *
     * This handler covers ALL ClientAware exceptions — any new Lighthouse
     * exception type that implements ClientAware is handled automatically
     * without adding another renderable callback.
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