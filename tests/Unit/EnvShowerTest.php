<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Tests\Unit;

use Curology\EnvLoader\Command\EnvShower;
use Curology\EnvLoader\Config;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EnvShowerTest extends TestCase
{
    public function testGetTableDataFailsOnMissing(): void
    {
        $envShower = new EnvShower();
        $envShowerReflection = new \ReflectionClass($envShower);
        $config = Config::loadFromArray([
            'parameterList' => ['missing_from_ssm:1'],
            'awsRegion' => 'us-east-1',
            'parameterPrefix' => '',
            'envOverridePath' => 'tests/fixtures/env/.env.override',
        ]);

        ReflectionHelper::setReflectionProperty(
            $envShower,
            $envShowerReflection,
            'config',
            $config
        );

        $vars = ['VAR1' => 'value1', 'VAR2' => 'value2'];

        $expected = [
            ['VAR1', '-', 'value1'],
            ['VAR2', '-', 'value2'],
            ['MISSING_FROM_SSM', 'missing_from_ssm:1', '<fg=red>VARIABLE NOT FOUND</fg=red>'],
            ['OVERRIDDEN', '-', '<fg=red>VARIABLE NOT FOUND</fg=red>'],
            ['EXTRA', '-', '<fg=red>VARIABLE NOT FOUND</fg=red>'],
        ];

        $this->assertSame(
            $expected,
            ReflectionHelper::callReflectionMethod($envShower, $envShowerReflection, 'getTableData', [$vars, true])
        );

        $expected[0][2] = '**********';
        $expected[1][2] = '**********';
        $this->assertSame($expected, ReflectionHelper::callReflectionMethod($envShower, $envShowerReflection, 'getTableData', [$vars, false]));
    }
}
