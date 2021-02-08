<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Tests\Command;

use Curology\EnvLoader\Command\EnvShower;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class EnvShowerCommandTest extends TestCase
{
    protected function setUp(): void
    {
        $app = new Application();
        $app->add(new EnvShower());
        $command = $app->find('show');

        $this->commandTester = new CommandTester($command);
    }

    public function testEnvShower(): void
    {
        $this->commandTester->execute(['config-file' => 'tests/fixtures/config/test_envshower_config.json']);

        $this->assertSame(
            file_get_contents('tests/fixtures/output/test_envshower_expected.txt'),
            $this->commandTester->getDisplay()
        );
    }

    public function testEnvShowerWithValues(): void
    {
        $this->commandTester->execute([
            'config-file' => 'tests/fixtures/config/test_envshower_config.json',
            '--with-values' => true,
        ]);

        $this->assertSame(
            file_get_contents('tests/fixtures/output/test_envshower_with_values_expected.txt'),
            $this->commandTester->getDisplay()
        );
    }
}
