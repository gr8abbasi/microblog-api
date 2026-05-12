<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'body',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Automatically trim whitespace when setting body.
     */
    public function setBodyAttribute(string $value): void
    {
        $this->attributes['body'] = trim($value);
    }

    /**
     * The author of this post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}