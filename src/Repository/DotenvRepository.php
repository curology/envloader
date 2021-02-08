<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Repository;

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;

class DotenvRepository implements RepositoryInterface
{
    private string $workingDir;
    private string $envFile;

    public function __construct(string $workingDir, string $envFile)
    {
        $this->workingDir = $workingDir;
        $this->envFile = $envFile;
    }

    public function load(): array
    {
        $adapter = new ArrayAdapter();

        $repo = RepositoryBuilder::create()
            ->withReaders([$adapter])
            ->withWriters([$adapter])
            ->immutable()
            ->make()
        ;

        return Dotenv::create($repo, $this->workingDir, $this->envFile)->load();
    }
}
