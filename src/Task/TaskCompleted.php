<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Task;

use EasyCorp\Bundle\EasyDeployBundle\Server\Server;

/**
 * It is an immutable object that encapsulates the result of executing a task
 * and provides helper methods to get all its information.
 */
class TaskCompleted
{
    private $server;
    private $output;
    private $exitCode;

    public function __construct(Server $server, string $output, int $exitCode)
    {
        $this->server = $server;
        $this->output = $output;
        $this->exitCode = $exitCode;
    }

    public function isSuccessful(): bool
    {
        return 0 === $this->exitCode;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getTrimmedOutput(): string
    {
        return trim($this->output);
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }
}
