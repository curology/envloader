<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Repository;

use Aws\Credentials\Credentials;
use Aws\Ssm\SsmClient;
use Curology\EnvLoader\Config;
use Curology\EnvLoader\Exception\InvalidParameterException;

class SsmRepository implements RepositoryInterface
{
    /**
     * @var string[]
     */
    private array $parameterPaths;
    private string $parameterPrefix;
    private SsmClient $ssmClient;

    public function __construct(string $parameterPrefix, array $parameterPaths, SsmClient $ssmClient)
    {
        $this->parameterPrefix = $parameterPrefix;
        $this->parameterPaths = $parameterPaths;
        $this->ssmClient = $ssmClient;
    }

    public static function create(
        string $parameterPrefix,
        array $parameterPaths,
        string $awsRegion,
        string $awsProfile = 'default'
    ): self {
        return new static(
            $parameterPrefix,
            $parameterPaths,
            static::getSsmClient($awsProfile, $awsRegion)
        );
    }

    public function load(): array
    {
        $parameters = $this->getParameters();

        return array_combine(
            array_map(
                fn (string $name): string => self::getEnvNameFromParameterName($this->parameterPrefix, $name),
                array_keys($parameters)
            ),
            array_values($parameters)
        );
    }

    public static function validateParameterPaths(array $parameterPaths): void
    {
        foreach ($parameterPaths as $parameterPath) {
            if (1 !== preg_match('/\A[a-zA-Z0-9_.\-\/]+:\d+\z/', $parameterPath)) {
                throw new InvalidParameterException(
                    "Invalid form for parameter: {$parameterPath}. Parameters must be of the form `{\$name}:{\$version}`."
                );
            }
        }
    }

    public static function getEnvNameFromParameterName(string $prefix, string $name): string
    {
        [$name, $version] = explode(':', strtoupper(Config::getParameterNameWithoutPrefix($prefix, $name)));

        return $name;
    }

    private static function getSsmClient(string $awsProfile, string $awsRegion): SsmClient
    {
        $awsEnvCreds = static::getAwsEnvCreds();

        return new SsmClient(array_merge(
            ['version' => '2014-11-06', 'region' => $awsRegion],
            static::getSsmClientAuthOptions($awsProfile, $awsEnvCreds)
        ));
    }

    private static function getAwsEnvCreds(): array
    {
        return [
            'AWS_SSM_ACCESS_KEY_ID' => getenv('AWS_SSM_ACCESS_KEY_ID'),
            'AWS_SSM_SECRET_ACCESS_KEY' => getenv('AWS_SSM_SECRET_ACCESS_KEY'),
            'AWS_ACCESS_KEY_ID' => getenv('AWS_ACCESS_KEY_ID'),
            'AWS_SECRET_ACCESS_KEY' => getenv('AWS_SECRET_ACCESS_KEY'),
        ];
    }

    private static function getSsmClientAuthOptions(string $awsProfile, array $awsEnvCreds): array
    {
        if ($awsEnvCreds['AWS_SSM_ACCESS_KEY_ID'] && $awsEnvCreds['AWS_SSM_SECRET_ACCESS_KEY']) {
            return ['credentials' => new Credentials(
                $awsEnvCreds['AWS_SSM_ACCESS_KEY_ID'],
                $awsEnvCreds['AWS_SSM_SECRET_ACCESS_KEY']
            )];
        }

        if ('default' !== $awsProfile) {
            return ['profile' => $awsProfile];
        }

        if ($awsEnvCreds['AWS_ACCESS_KEY_ID'] && $awsEnvCreds['AWS_SECRET_ACCESS_KEY']) {
            return ['credentials' => new Credentials(
                $awsEnvCreds['AWS_ACCESS_KEY_ID'],
                $awsEnvCreds['AWS_SECRET_ACCESS_KEY']
            )];
        }

        return ['profile' => $awsProfile];
    }

    private function getParameters(): array
    {
        $parameters = [];

        // SSM will only allow fetching 10 parameters at a time
        foreach (array_chunk($this->parameterPaths, 10) as $parameterChunk) {
            $response = $this->ssmClient->getParameters([
                'Names' => $parameterChunk,
                'WithDecryption' => true,
            ]);

            // Error if parameters can't be found in SSM
            if (!empty($response['InvalidParameters'])) {
                $invalidParams = print_r($response['InvalidParameters'], true);

                throw new InvalidParameterException("Cannot find the following parameters in SSM: {$invalidParams}");
            }

            foreach ($response['Parameters'] as $responseParam) {
                $parameters[$responseParam['Name'].':'.$responseParam['Version']] = $responseParam['Value'];
            }
        }
        // retain ordering from $this->parameterPaths since AWS will return the parameters in alphabetical order
        return array_combine(
            $this->parameterPaths,
            array_map(fn (string $parameterPath) => $parameters[$parameterPath], $this->parameterPaths)
        );
    }
}
