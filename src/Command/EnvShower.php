<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Command;

use Curology\EnvLoader\Config;
use Curology\EnvLoader\Repository\DotenvRepository;
use Curology\EnvLoader\Repository\SsmRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EnvShower extends Command
{
    private const MISSING_PLACEHOLDER = '<fg=red>VARIABLE NOT FOUND</fg=red>';
    private Config $config;

    protected function configure()
    {
        $this
            ->setName('show')
            ->setDescription('Show key, value pairs from the generated dotenv file')
            ->addArgument('config-file', InputArgument::OPTIONAL, 'The json file containing your configurations', 'envloader.json')
            ->addOption(
                'with-values',
                null,
                InputOption::VALUE_NONE,
                'If supplied, the dotenv variable values will be displayed. Otherwise, only the names will be shown in the output.',
            )
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command shows the key, value pairs from the generated dotenv file, along
with their corresponding names in AWS SSM Parameter Store.

By default, the values are obfuscated:

  <info>%command.full_name%</info>

You can also choose to reveal the values:

  <info>%command.full_name% --with-values</info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->config = Config::loadFromFile($input->getArgument('config-file'));

        $output->writeln('Showing variables for');
        $output->writeln('environment: "'.$this->config->environment.'"');
        $output->writeln('parameter prefix: "'.$this->config->parameterPrefix.'"');

        $showValues = $input->getOption('with-values');
        $vars = (new DotenvRepository($this->config->workingDir, $this->config->envPath))->load();

        $tableData = $this->getTableData($vars, $showValues);
        self::renderEnvTable($output, $tableData);

        return Command::SUCCESS;
    }

    private function getTableData(array $vars, bool $showValues): array
    {
        $envToParamName = [];
        foreach ($this->config->parameterPaths as $fullParamName) {
            $envName = SsmRepository::getEnvNameFromParameterName($this->config->parameterPrefix, $fullParamName);
            if (!array_key_exists($envName, $vars)) {
                $vars[$envName] = self::MISSING_PLACEHOLDER;
            }
            $envToParamName[$envName] = $fullParamName;
        }

        if (!empty($this->config->envOverridePath)) {
            $overrides = (new DotenvRepository($this->config->workingDir, $this->config->envOverridePath))->load();

            foreach ($overrides as $overrideName => $overrideValue) {
                if (!array_key_exists($overrideName, $vars)) {
                    $vars[$overrideName] = self::MISSING_PLACEHOLDER;
                }
            }
        }

        return array_map(function (string $envName) use ($envToParamName, $showValues, $vars): array {
            return [
                $envName,
                empty($envToParamName[$envName])
                    ? '-'
                    : Config::getParameterNameWithoutPrefix($this->config->parameterPrefix, $envToParamName[$envName]),
                ($showValues || (self::MISSING_PLACEHOLDER === $vars[$envName])) ? $vars[$envName] : '**********',
            ];
        }, array_keys($vars));
    }

    private static function renderEnvTable(OutputInterface $output, array $vars): void
    {
        $table = new Table($output);
        $table
            ->setColumnMaxWidth(0, 30)
            ->setColumnMaxWidth(1, 30)
            ->setColumnMaxWidth(2, 70)
            ->setHeaders(['Variable Name', 'Parameter Name', 'Value'])
            ->setRows($vars)
            ->render()
        ;
    }
}
