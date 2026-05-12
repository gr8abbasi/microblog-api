<?php

namespace Tests\Feature\GraphQL;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MicroblogTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Global Feed
    // -------------------------------------------------------------------------

    #[Test]
    public function global_feed_returns_paginated_posts_with_correct_structure(): void
    {
        User::factory(3)
            ->has(Post::factory()->count(5))
            ->create();

        $response = $this->postJson('/graphql', [
            'query' => '
                {
                    feed(first: 10) {
                        data {
                            id
                            body
                            createdAt
                            user {
                                id
                                username
                            }
                        }
                        paginatorInfo {
                            total
                            hasMorePages
                            currentPage
                        }
                    }
                }
            ',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.feed.paginatorInfo.total', 15)
            ->assertJsonPath('data.feed.paginatorInfo.currentPage', 1)
            ->assertJsonStructure([
                'data' => [
                    'feed' => [
                        'data' => [
                            '*' => [
                                'id',
                                'body',
                                'createdAt',
                                'user' => ['id', 'username'],
                            ],
                        ],
                        'paginatorInfo' => [
                            'total',
                            'hasMorePages',
                            'currentPage',
                        ],
                    ],
                ],
            ]);
    }

    #[Test]
    public function global_feed_returns_posts_newest_first(): void
    {
        $user = User::factory()->create();
        $old  = Post::factory()->create([
            'user_id'    => $user->id,
            'created_at' => now()->subDays(5),
        ]);
        $new = Post::factory()->create([
            'user_id'    => $user->id,
            'created_at' => now(),
        ]);

        $response = $this->postJson('/graphql', [
            'query' => '{ feed(first: 10) { data { id } } }',
        ]);

        $ids = collect($response->json('data.feed.data'))->pluck('id');
        $this->assertEquals((string) $new->id, $ids->first());
        $this->assertEquals((string) $old->id, $ids->last());
    }

    // -------------------------------------------------------------------------
    // User Profile
    // -------------------------------------------------------------------------

    #[Test]
    public function user_profile_returns_user_with_their_posts(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);
        Post::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->postJson('/graphql', [
            'query' => '
                {
                    user(username: "testuser") {
                        id
                        name
                        username
                        posts(first: 10) {
                            data {
                                id
                                body
                            }
                            paginatorInfo {
                                total
                            }
                        }
                    }
                }
            ',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.username', 'testuser')
            ->assertJsonPath('data.user.posts.paginatorInfo.total', 3);
    }

    #[Test]
    public function user_profile_returns_null_for_unknown_username(): void
    {
        $response = $this->postJson('/graphql', [
            'query' => '{ user(username: "nobody") { id username } }',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user', null);
    }

    // -------------------------------------------------------------------------
    // Post Creation
    // -------------------------------------------------------------------------

    #[Test]
    public function authenticated_user_can_create_a_post(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/graphql', [
            'query' => '
                mutation {
                    createPost(input: { body: "Hello Zellerfeld!" }) {
                        id
                        body
                        user { username }
                    }
                }
            ',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.createPost.body', 'Hello Zellerfeld!')
            ->assertJsonPath('data.createPost.user.username', $user->username);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'body'    => 'Hello Zellerfeld!',
        ]);
    }

    #[Test]
    public function post_body_exceeding_280_characters_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $body = str_repeat('a', 281);

        $response = $this->postJson('/graphql', [
            'query' => "mutation { createPost(input: { body: \"{$body}\" }) { id } }",
        ]);

        $response->assertOk();
        $this->assertNotNull($response->json('errors'));
        $this->assertDatabaseCount('posts', 0);
    }

    #[Test]
    public function post_body_of_whitespace_only_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/graphql', [
            'query' => 'mutation { createPost(input: { body: "     " }) { id } }',
        ]);

        $response->assertOk();
        $this->assertNotNull($response->json('errors'));
        $this->assertDatabaseCount('posts', 0);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_a_post(): void
    {
        $response = $this->postJson('/graphql', [
            'query' => '
                mutation {
                    createPost(input: { body: "Should not be created" }) {
                        id
                    }
                }
            ',
        ]);

        // Lighthouse returns 200 with error in body — correct GraphQL behaviour
        // AuthenticationException is handled by bootstrap/app.php workaround
        // for Lighthouse v6.66 + Laravel 13 compatibility
        $response->assertOk();
        $this->assertNotNull($response->json('errors'));
        $this->assertEquals('Unauthenticated.', $response->json('errors.0.message'));
        $this->assertDatabaseCount('posts', 0);
    }
}