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
use EasyCorp\Bundle\EasyDeployBundle\Server\Property;
use EasyCorp\Bundle\EasyDeployBundle\Server\Server;
use Symfony\Component\Process\Process;

class TaskRunner
{
    private $isDryRun;
    private $logger;

    public function __construct(bool $isDryRun, Logger $logger)
    {
        $this->isDryRun = $isDryRun;
        $this->logger = $logger;
    }

    /**
     * @return TaskCompleted[]
     */
    public function run(Task $task): array
    {
        $results = [];
        foreach ($task->getServers() as $server) {
            $results[] = $this->doRun($server, $server->resolveProperties($task->getShellCommand()), $task->getEnvVars());
        }

        return $results;
    }

    private function createProcess(string $shellCommand): Process
    {
        if (method_exists(Process::class, 'fromShellCommandline')) {
            return Process::fromShellCommandline($shellCommand);
        }

        return new Process($shellCommand);
    }

    private function doRun(Server $server, string $shellCommand, array $envVars): TaskCompleted
    {
        if ($server->has(Property::project_dir)) {
            $shellCommand = sprintf('cd %s && %s', $server->get(Property::project_dir), $shellCommand);
        }

        // env vars aren't set with $process->setEnv() because it causes problems
        // that can't be fully solved with inheritEnvironmentVariables()
        if (!empty($envVars)) {
            $envVarsAsString = http_build_query($envVars, '', ' ');
            // the ';' after the env vars makes them available to all commands, not only the first one
            // parenthesis create a sub-shell so the env vars don't affect to the parent shell
            $shellCommand = sprintf('(export %s; %s)', $envVarsAsString, $shellCommand);
        }

        $this->logger->log(sprintf('[<server>%s</>] Executing command: <command>%s</>', $server, $shellCommand));

        if ($this->isDryRun) {
            return new TaskCompleted($server, '', 0);
        }

        if ($server->isLocalHost()) {
            $process = $this->createProcess($shellCommand);
        } else {
            $process = $this->createProcess(sprintf('%s %s', $server->getSshConnectionString(), escapeshellarg($shellCommand)));
        }

        $process->setTimeout(null);

        $process = $process->mustRun(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->logger->log(Str::prefix(rtrim($buffer, PHP_EOL), '| <stream>err ::</> '));
            } else {
                $this->logger->log(Str::prefix(rtrim($buffer, PHP_EOL), '| <stream>out ::</> '));
            }
        });

        return new TaskCompleted($server, $process->getOutput(), $process->getExitCode());
    }
}
