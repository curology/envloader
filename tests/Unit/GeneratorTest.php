<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Tests\Unit;

use Curology\EnvLoader\Exception\InvalidPathException;
use Curology\EnvLoader\Generator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class GeneratorTest extends TestCase
{
    public function testWrite(): void
    {
        $vars = [
            'OVERRIDDEN' => 'old value',
            'NOT_OVERRIDDEN' => 'not overridden value',
        ];

        $overrides = [
            'OVERRIDDEN' => 'new value',
            'EXTRA_OVERRIDE' => 'extra override value',
        ];

        $path = 'tests/artifacts/.env.testWrite';
        Generator::write($path, $vars, $overrides);

        $this->assertSame(
            file_get_contents('tests/fixtures/env/.env.testWriteExpected'),
            file_get_contents($path)
        );
    }

    public function testWriteFailsOnMissingDir(): void
    {
        $path = '';
        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage("Unable to write dotenv file to the directory: {$path}. Make sure the directory exists before running envloader.");
        Generator::write($path, [], []);
    }

    public function testEscapeEnvValueEmptyString(): void
    {
        $values = ['', '""', "''"];
        foreach ($values as $value) {
            $this->assertSame($value, Generator::escapeEnvValue($value));
        }
    }

    public function testEscapeEnvValueBackslashAndDoubleQuotesEscaped(): void
    {
        $values = [
            'hello\\' => '"hello\\\\"',
            '\\hell\\o' => '"\\\\hell\\\\o"',
            '"hello"' => '"\\"hello\\""',
            'hell"o' => '"hell\\"o"',
        ];
        foreach ($values as $input => $expected) {
            $this->assertSame($expected, Generator::escapeEnvValue($input));
        }
    }
}
