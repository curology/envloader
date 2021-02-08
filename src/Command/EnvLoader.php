<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Command;

use Curology\EnvLoader\Config;
use Curology\EnvLoader\Generator;
use Curology\EnvLoader\Repository\DotenvRepository;
use Curology\EnvLoader\Repository\SsmRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnvLoader extends Command
{
    private Config $config;
    private SsmRepository $ssmRepository;

    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Loads parameters from AWS SSM Parameter store and uses them to generate a dotenv file')
            ->addArgument('config-file', InputArgument::OPTIONAL, 'The json file containing your configurations', 'envloader.json')
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command generates a dotenv file using the config file named <comment>envloader.json</comment>
in your current working directory:

  <info>%command.full_name%</info>

You can also override the default config path by specifying the path to your config file:

  <info>%command.full_name% PATH/TO/YOUR/CONFIG/FILE </info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->config = Config::loadFromFile($input->getArgument('config-file'));

        $output->writeln("Generating dotenv file for: {$this->config->environment}");

        Generator::write(
            $this->config->getFullEnvPath(),
            SsmRepository::create(
                $this->config->parameterPrefix,
                $this->config->parameterPaths,
                $this->config->awsRegion,
                $this->config->awsProfile
            )->load(),
            empty($this->config->envOverridePath)
                ? []
                : (new DotenvRepository($this->config->workingDir, $this->config->envOverridePath))->load(),
        );

        $output->writeln('Success!');

        return Command::SUCCESS;
    }
}
