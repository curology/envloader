<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Tests\Unit;

use Aws\Credentials\Credentials;
use Aws\MockHandler;
use Aws\Result;
use Aws\Ssm\SsmClient;
use Curology\EnvLoader\Exception\InvalidParameterException;
use Curology\EnvLoader\Repository\SsmRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SsmRepositoryTest extends TestCase
{
    public static function getSsmRepositoryAndSetSsmMock(
        array $mockResponses,
        array $parameterPaths,
        string $parameterPrefix = ''
    ): SsmRepository {
        $mockSsmHandler = new MockHandler();
        foreach ($mockResponses as $mockResponse) {
            $mockSsmHandler->append(new Result($mockResponse));
        }

        $ssmClient = new SsmClient([
            'region' => 'us-east-1',
            'credentials' => new Credentials('key', 'secret'),
            'version' => '2014-11-06',
            'handler' => $mockSsmHandler,
        ]);

        return new SsmRepository($parameterPrefix, $parameterPaths, $ssmClient);
    }

    public function testLoadFailsOnInvalid(): void
    {
        $response = [
            'Parameters' => [],
            'InvalidParameters' => ['invalidKey:1'],
        ];

        $ssmRepository = self::getSsmRepositoryAndSetSsmMock([$response], ['invalidKey:1']);

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Cannot find the following parameters in SSM: '.print_r($response['InvalidParameters'], true));
        $ssmRepository->load();
    }

    public function testLoadPaginated(): void
    {
        $parameterPaths = [];
        $responseParameters = [];
        $expected = [];
        foreach (range(0, 10, 1) as $i) {
            $parameterPaths[$i] = "key{$i}:{$i}";
            $responseParameters[$i] = ['Name' => "key{$i}", 'Value' => "value{$i}", 'Version' => $i];
            $expected["KEY{$i}"] = "value{$i}";
        }

        $responses = [
            ['Parameters' => array_slice($responseParameters, 0, 10), 'InvalidParameters' => []],
            ['Parameters' => array_slice($responseParameters, 10, 10), 'InvalidParameters' => []],
        ];

        $ssmRepository = self::getSsmRepositoryAndSetSsmMock($responses, $parameterPaths);

        $parameters = $ssmRepository->load();
        $this->assertSame($expected, $parameters);
    }

    public function badParameterProvider(): array
    {
        return [
            [['noVersionParam1']],
            [['noVersionParam2:']],
            [['twoVersionsParam:2:1']],
            [['badVersionParam1:1a']],
            [['badVersionParam1:version']],
            [['invalidChars&%$#@Param:1']],
            [['']],
            [[':']],
            [[':1']],
        ];
    }

    /**
     * @dataProvider badParameterProvider
     */
    public function testValidateParameterPathsFailsOnBadParameter(array $badParameters): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage("Invalid form for parameter: {$badParameters[0]}. Parameters must be of the form `{\$name}:{\$version}`.");
        SsmRepository::validateParameterPaths($badParameters);
    }

    public function testGetEnvNameFromParameterNameNoPrefix(): void
    {
        $this->assertSame('NAME', SsmRepository::getEnvNameFromParameterName('', 'name:1'));
    }

    public function testGetEnvNameFromParameterNameWithPrefix(): void
    {
        $this->assertSame('NAME', SsmRepository::getEnvNameFromParameterName('/prefix/', 'name:12'));
        $this->assertSame('NAME', SsmRepository::getEnvNameFromParameterName('/prefix/', '/prefix/name:12'));
    }

    public function testGetSsmClientAuthOptionsHierarchy(): void
    {
        $ssmRepository = self::getSsmRepositoryAndSetSsmMock([], []);
        $ssmReflection = new \ReflectionClass($ssmRepository);

        $profile = 'profile_name';
        $awsEnvCreds = [
            'AWS_SSM_ACCESS_KEY_ID' => 'aws_ssm_key',
            'AWS_SSM_SECRET_ACCESS_KEY' => 'aws_ssm_secret',
            'AWS_ACCESS_KEY_ID' => 'aws_key',
            'AWS_SECRET_ACCESS_KEY' => 'aws_secret',
        ];

        $authOptions = ReflectionHelper::callReflectionMethod(
            $ssmRepository,
            $ssmReflection,
            'getSsmClientAuthOptions',
            [$profile, $awsEnvCreds]
        );
        $this->assertEquals('aws_ssm_key', $authOptions['credentials']->getAccessKeyId());
        $this->assertEquals('aws_ssm_secret', $authOptions['credentials']->getSecretKey());
        $this->assertEquals(['credentials'], array_keys($authOptions));

        $awsEnvCreds['AWS_SSM_ACCESS_KEY_ID'] = false;

        $authOptions = ReflectionHelper::callReflectionMethod(
            $ssmRepository,
            $ssmReflection,
            'getSsmClientAuthOptions',
            [$profile, $awsEnvCreds]
        );
        $this->assertEquals('profile_name', $authOptions['profile']);
        $this->assertEquals(['profile'], array_keys($authOptions));

        $profile = 'default';

        $authOptions = ReflectionHelper::callReflectionMethod(
            $ssmRepository,
            $ssmReflection,
            'getSsmClientAuthOptions',
            [$profile, $awsEnvCreds]
        );
        $this->assertEquals('aws_key', $authOptions['credentials']->getAccessKeyId());
        $this->assertEquals('aws_secret', $authOptions['credentials']->getSecretKey());
        $this->assertEquals(['credentials'], array_keys($authOptions));

        $awsEnvCreds['AWS_ACCESS_KEY_ID'] = false;
        $awsEnvCreds['AWS_SECRET_ACCESS_KEY'] = false;

        $authOptions = ReflectionHelper::callReflectionMethod(
            $ssmRepository,
            $ssmReflection,
            'getSsmClientAuthOptions',
            [$profile, $awsEnvCreds]
        );
        $this->assertEquals('default', $authOptions['profile']);
        $this->assertEquals(['profile'], array_keys($authOptions));
    }
}
