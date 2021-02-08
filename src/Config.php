<?php

declare(strict_types=1);

namespace Curology\EnvLoader;

use Curology\EnvLoader\Exception\InvalidFileException;
use Curology\EnvLoader\Repository\SsmRepository;

class Config
{
    /**
     * @var string[]
     */
    public array $parameterPaths;
    public string $awsRegion;
    public string $awsProfile;
    public string $environment;
    public string $envPath;
    public string $envOverridePath;
    public string $workingDir;
    public string $parameterPrefix;

    public function __construct(
        array $parameterList,
        string $awsRegion,
        string $awsProfile = 'default',
        string $environment = 'default',
        string $envPath = '.env',
        string $envOverridePath = '',
        string $workingDir = '',
        string $parameterPrefix = ''
    ) {
        $this->parameterPaths = static::getParameterPaths($parameterPrefix, $parameterList);
        SsmRepository::validateParameterPaths($this->parameterPaths);

        $this->awsRegion = $awsRegion;
        $this->awsProfile = $awsProfile;
        $this->environment = $environment;
        $this->envPath = $envPath;
        $this->envOverridePath = $envOverridePath;
        $this->workingDir = $workingDir ?? getcwd();
        $this->parameterPrefix = $parameterPrefix;
    }

    public static function getParameterPaths(string $prefix, array $parameterList): array
    {
        return array_map(
            fn (string $parameterName): string => $prefix.$parameterName,
            $parameterList
        );
    }

    public static function loadFromFile(string $configFile): self
    {
        return static::loadFromArray(static::parseConfigFile($configFile));
    }

    public static function loadFromArray(array $configArray): self
    {
        return new static(
            $configArray['parameterList'],
            $configArray['awsRegion'],
            $configArray['awsProfile'] ?? 'default',
            $configArray['environment'] ?? 'default',
            $configArray['envPath'] ?? '.env',
            $configArray['envOverridePath'] ?? '',
            $configArray['workingDir'] ?? getcwd(),
            $configArray['parameterPrefix'] ?? '',
        );
    }

    public function getFullEnvPath(): string
    {
        return $this->getFullPath($this->envPath);
    }

    public function getFullOverridePath(): string
    {
        return $this->getFullPath($this->envOverridePath);
    }

    public static function getParameterNameWithoutPrefix(string $prefix, string $name): string
    {
        return (substr($name, 0, strlen($prefix)) === $prefix)
            ? substr($name, strlen($prefix))
            : $name;
    }

    private static function parseConfigFile(string $configFile): array
    {
        if (!file_exists($configFile)) {
            throw new InvalidFileException("Unable to find config file: {$configFile}");
        }

        $configContents = file_get_contents($configFile);
        if (false === $configContents) {
            throw new InvalidFileException("Unable to read config file: {$configFile}");
        }

        $config = json_decode($configContents, true);
        if (null === $config) {
            throw new InvalidFileException('Error: `'.json_last_error_msg()."` when decoding config file: {$configFile}");
        }

        return $config;
    }

    private function getFullPath(string $path): string
    {
        return $this->workingDir.DIRECTORY_SEPARATOR.$path;
    }
}
