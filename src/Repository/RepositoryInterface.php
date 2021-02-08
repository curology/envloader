<?php

declare(strict_types=1);

namespace Curology\EnvLoader\Repository;

interface RepositoryInterface
{
    /**
     * Returns a map of environment variable name => value.
     *
     * @return string[string]
     */
    public function load(): array;
}
