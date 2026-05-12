<?php

declare(strict_types=1);

namespace App\Domain\Post\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PostBodyRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (trim($value) === '') {
            $fail('A post cannot be empty or contain only whitespace.');
        }
    }
}