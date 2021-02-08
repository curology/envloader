<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Tests\Unit;

use Curology\EnvLoader\Config;
use Curology\EnvLoader\Exception\InvalidFileException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ConfigTest extends TestCase
{
    public function testLoadFromFileFailsOnMissingFile(): void
    {
        $configFile = '';
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage("Unable to find config file: {$configFile}");
        Config::loadFromFile($configFile);
    }

    public function testLoadFromFileFailsOnInvalidFile(): void
    {
        $configFile = 'tests/fixtures/config/bad_syntax.json';
        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage("Error: `Syntax error` when decoding config file: {$configFile}");
        Config::loadFromFile($configFile);
    }

    public function compareConfigWithDefaults(array $configArray, Config $config): void
    {
        $this->assertSame($configArray['awsRegion'], $config->awsRegion);
        $this->assertSame($configArray['parameterList'], $config->parameterPaths);
        $this->assertSame('default', $config->awsProfile);
        $this->assertSame('default', $config->environment);
        $this->assertSame('.env', $config->envPath);
        $this->assertSame('', $config->envOverridePath);
        $this->assertSame(getcwd(), $config->workingDir);
        $this->assertSame('', $config->parameterPrefix);
    }

    public function testLoadDefaults(): void
    {
        $configArray = ['awsRegion' => 'us-east-1', 'parameterList' => ['param1:1', 'param2:2']];
        $config = Config::loadFromArray($configArray);
        self::compareConfigWithDefaults($configArray, $config);

        $config = Config::loadFromFile('tests/fixtures/config/only_required.json');
        self::compareConfigWithDefaults($configArray, $config);
    }

    public function testGetParameterPathsWithoutPrefix(): void
    {
        $parameterList = ['param_1:1', 'param-2:2'];
        $parameterPaths = Config::getParameterPaths('', $parameterList);
        $this->assertSame($parameterList, $parameterPaths);
    }

    public function testGetParameterPathsWithPrefix(): void
    {
        $parameterList = ['param.1:1', 'param.2:2'];
        $parameterPaths = Config::getParameterPaths('prefix/', $parameterList);
        $this->assertSame(['prefix/param.1:1', 'prefix/param.2:2'], $parameterPaths);
    }
}
