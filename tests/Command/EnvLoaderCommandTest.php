<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Tests\Command;

use Curology\EnvLoader\Command\EnvLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class EnvLoaderCommandTest extends TestCase
{
    protected function setUp(): void
    {
        if (!('true' === getenv('ENVLOADER_TEST_ENABLE_AWS'))) {
            $this->markTestSkipped('Skipping EnvLoader Test - ENVLOADER_TEST_ENABLE_AWS not set.');
        }

        $app = new Application();
        $app->add(new EnvLoader());
        $command = $app->find('generate');

        $this->commandTester = new CommandTester($command);
    }

    public function testEnvLoader(): void
    {
        $this->commandTester->execute(['config-file' => 'tests/fixtures/config/test_envloader_config.json']);

        $this->assertStringContainsString(
            'Generating dotenv file for: test'.PHP_EOL.'Success!',
            $this->commandTester->getDisplay()
        );

        $this->assertSame(
            file_get_contents('tests/fixtures/env/.env'),
            file_get_contents('tests/artifacts/.env.testEnvLoader')
        );
    }
}
