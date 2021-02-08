<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Tests\Unit;

use Curology\EnvLoader\Repository\DotenvRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DotenvRepositoryTest extends TestCase
{
    public function testLoad(): void
    {
        $this->assertSame(
            [
                'EMPTY' => '',
                'EMPTY2' => '',
                'NUMERIC' => '5',
                'SIMPLE' => 'hello',
                'BACKSLASH' => 'test\\backslash',
                'NO_SUBSTITUTION' => '$SIMPLE',
                'SUBSTITUTION' => 'hello',
                'DOUBLE_QUOTES' => '"hello world"',
                'SINGLE_QUOTES' => '\'hello world\'',
                'BACKTICKS' => '`pwd`',
                'SPECIAL_CHARS' => 'skdlf2o3i~2u304&**@%&#%@^jsllvuwecn',
                'NOT_SECURE' => 'plain text',
                'OVERRIDDEN' => 'new value',
                'EXTRA' => 'kwdhf928eishd',
            ],
            (new DotenvRepository(getcwd(), 'tests/fixtures/env/.env'))->load()
        );
    }
}
