<?php

declare(strict_types=1);

namespace Curology\EnvLoader;

use Curology\EnvLoader\Exception\InvalidPathException;

class Generator
{
    private const DOTENV_TEMPLATE = <<<'TPL'
############################
# From AWS
############################
{vars}

############################
# Overrides
############################
{overrides}

TPL;

    public static function write(string $path, array $vars, array $overrides): void
    {
        self::checkDirExists(dirname($path));

        $templateVars = implode(
            PHP_EOL,
            array_map(function (string $envName) use ($vars, $overrides): string {
                $commentString = array_key_exists($envName, $overrides) ? '# ' : '';

                return $commentString.$envName.'='.self::escapeEnvValue($vars[$envName]);
            }, array_keys($vars))
        );

        $templateOverrides = implode(
            PHP_EOL,
            array_map(function (string $envName) use ($overrides): string {
                return $envName.'='.self::escapeEnvValue($overrides[$envName]);
            }, array_keys($overrides))
        );

        file_put_contents(
            $path,
            strtr(self::DOTENV_TEMPLATE, ['{vars}' => $templateVars, '{overrides}' => $templateOverrides])
        );
    }

    public static function escapeEnvValue(string $envValue): string
    {
        // Interpret empty quotes as empty string
        if ('' === $envValue || '""' === $envValue || "''" === $envValue) {
            return $envValue;
        }

        if (is_numeric($envValue)) {
            return $envValue;
        }

        $envValue = str_replace('\\', '\\\\', $envValue);
        $envValue = str_replace('"', '\\"', $envValue);

        return '"'.$envValue.'"';
    }

    private static function checkDirExists(string $dir): void
    {
        if (!is_dir($dir)) {
            throw new InvalidPathException(
                "Unable to write dotenv file to the directory: {$dir}. Make sure the directory exists before running envloader."
            );
        }
    }
}
