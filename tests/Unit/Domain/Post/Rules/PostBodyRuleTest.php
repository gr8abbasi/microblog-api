<?php

namespace Tests\Unit\Domain\Post\Rules;

use App\Domain\Post\Rules\PostBodyRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PostBodyRuleTest extends TestCase
{
    private PostBodyRule $rule;
    private bool $failed;
    private string $failMessage;

    protected function setUp(): void
    {
        $this->rule        = new PostBodyRule();
        $this->failed      = false;
        $this->failMessage = '';
    }

    private function validate(mixed $value): void
    {
        $this->failed      = false;
        $this->failMessage = '';

        $this->rule->validate('body', $value, function (string $message) {
            $this->failed      = true;
            $this->failMessage = $message;
        });
    }

    public static function validBodies(): array
    {
        return [
            'plain text'                => ['Hello world!'],
            'surrounding whitespace'    => ['  valid post  '],
            'single character'          => ['a'],
            'long valid body'           => [str_repeat('a', 281)],
        ];
    }

    #[Test]
    #[DataProvider('validBodies')]
    public function it_passes_for_valid_body(string $value): void
    {
        $this->validate($value);
        $this->assertFalse($this->failed);
    }

    public static function invalidBodies(): array
    {
        return [
            'empty string'      => ['',      'empty'],
            'spaces only'       => ['     ', 'whitespace'],
            'tabs only'         => ["\t\t\t", 'whitespace'],
            'newlines only'     => ["\n\n",   'whitespace'],
            'mixed whitespace'  => [" \t\n ", 'whitespace'],
        ];
    }

    #[Test]
    #[DataProvider('invalidBodies')]
    public function it_fails_for_invalid_body(string $value, string $expectedMessage): void
    {
        $this->validate($value);
        $this->assertTrue($this->failed);
        $this->assertStringContainsString($expectedMessage, $this->failMessage);
    }
}