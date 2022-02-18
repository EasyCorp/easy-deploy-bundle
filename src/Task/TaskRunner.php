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
        // Start all processes asynchronously
        $processes = [];
        foreach ($task->getServers() as $server) {
            $processes[] = $this->startProcess($server, $server->resolveProperties($task->getShellCommand()), $task->getEnvVars());
        }

        // Collect all their results
        $results = [];
        foreach ($processes as $process) {
            $results[] = $process->getCompletionResult();
        }

        return $results;
    }

    private function startProcess(Server $server, string $shellCommand, array $envVars): PendingTask
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

        $pendingTask = new PendingTask($server, $shellCommand, $this->logger, $this->isDryRun);
        $pendingTask->start();

        return $pendingTask;
    }
}
