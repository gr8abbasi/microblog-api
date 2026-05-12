<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 280);
            $table->timestamps();

            // Global feed: ORDER BY created_at DESC
            $table->index(['created_at', 'id'], 'posts_feed_index');

            // User profile: WHERE user_id = ? ORDER BY created_at DESC
            $table->index(['user_id', 'created_at'], 'posts_user_feed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};