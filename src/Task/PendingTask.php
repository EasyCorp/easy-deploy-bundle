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

use EasyCorp\Bundle\EasyDeployBundle\Helper\Str;
use EasyCorp\Bundle\EasyDeployBundle\Logger;
use EasyCorp\Bundle\EasyDeployBundle\Server\Server;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PendingTask {
    private $server;
    private $dryRun;
    private $process;
    private $logger;

    public function __construct (Server $server, string $shellCommand, Logger $logger, bool $dryRun = false)
    {
        $this->server = $server;
        $this->dryRun = $dryRun;
        $this->logger = $logger;

        if ($this->dryRun) {
            return;
        }

        if ($server->isLocalHost()) {
            $this->process = $this->createProcess($shellCommand);
        } else {
            $this->process = $this->createProcess(sprintf('%s %s', $server->getSshConnectionString(), escapeshellarg($shellCommand)));
        }

        $this->process->setTimeout(null);
    }

    private function createProcess(string $shellCommand): Process
    {
        if (method_exists(Process::class, 'fromShellCommandline')) {
            return Process::fromShellCommandline($shellCommand);
        }

        return new Process($shellCommand);
    }

    public function start ()
    {
        if ($this->dryRun) {
            return;
        }
        $this->process->start(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->logger->log(Str::prefix(rtrim($buffer, PHP_EOL), sprintf('| [<server>%s</>] <stream>err ::</> ', $this->server)));
            } else {
                $this->logger->log(Str::prefix(rtrim($buffer, PHP_EOL), sprintf('| [<server>%s</>] <stream>out ::</> ', $this->server)));
            }
        });
    }

    public function getCompletionResult ()
    {
        if ($this->dryRun) {
            return new TaskCompleted($this->server, '', 0);
        }

        // Make sure we ran without errors
        // As in https://github.com/symfony/symfony/blob/4.4/src/Symfony/Component/Process/Process.php#L266
        if (0 !== $this->process->wait()) {
            throw new ProcessFailedException($this->process);
        }

        return new TaskCompleted($this->server, $this->process->getOutput(), $this->process->getExitCode());
    }
}
