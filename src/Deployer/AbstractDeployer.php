<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Deployer;

use EasyCorp\Bundle\EasyDeployBundle\Configuration\ConfigurationAdapter;
use EasyCorp\Bundle\EasyDeployBundle\Configuration\Option;
use EasyCorp\Bundle\EasyDeployBundle\Context;
use EasyCorp\Bundle\EasyDeployBundle\Helper\Str;
use EasyCorp\Bundle\EasyDeployBundle\Logger;
use EasyCorp\Bundle\EasyDeployBundle\Requirement\AbstractRequirement;
use EasyCorp\Bundle\EasyDeployBundle\Server\Property;
use EasyCorp\Bundle\EasyDeployBundle\Server\Server;
use EasyCorp\Bundle\EasyDeployBundle\Server\ServerRepository;
use EasyCorp\Bundle\EasyDeployBundle\Task\Task;
use EasyCorp\Bundle\EasyDeployBundle\Task\TaskCompleted;
use EasyCorp\Bundle\EasyDeployBundle\Task\TaskRunner;

abstract class AbstractDeployer
{
    /** @var Context */
    private $context;
    /** @var TaskRunner */
    private $taskRunner;
    /** @var Logger */
    private $logger;
    /** @var ConfigurationAdapter */
    private $config;

    abstract public function getRequirements(): array;

    abstract public function deploy();

    abstract public function cancelDeploy();

    abstract public function rollback();

    final public function getConfig(string $name)
    {
        return $this->config->get($name);
    }

    final public function doDeploy(): void
    {
        try {
            $this->log('Executing <hook>beforeStartingDeploy</> hook');
            $this->beforeStartingDeploy();
            $this->log('<h1>Starting the deployment</>');

            $this->deploy();

            $this->log('Executing <hook>beforeFinishingDeploy</> hook');
            $this->beforeFinishingDeploy();
            $this->log('<h1>Finishing the deployment</>');
        } catch (\Exception $e) {
            $this->log('<error>[ERROR] Cancelling the deployment and reverting the changes</>');
            $this->log(sprintf('<error>A log file with all the error details has been generated in %s</>', $this->context->getLogFilePath()));

            $this->log('Executing <hook>beforeCancelingDeploy</> hook');
            $this->beforeCancelingDeploy();
            $this->cancelDeploy();

            throw $e;
        }

        $this->log(sprintf('<success>[OK] Deployment was successful</>'));
    }

    final public function doRollback(): void
    {
        try {
            $this->log('Executing <hook>beforeStartingRollback</> hook');
            $this->beforeStartingRollback();
            $this->log('<h1>Starting the rollback</>');

            $this->rollback();

            $this->log('Executing <hook>beforeFinishingRollback</> hook');
            $this->beforeFinishingRollback();
            $this->log('<h1>Finishing the rollback</>');
        } catch (\Exception $e) {
            $this->log('<error>[ERROR] The roll back failed because of the following error</>');
            $this->log(sprintf('<error>A log file with all the error details has been generated in %s</>', $this->context->getLogFilePath()));

            $this->log('Executing <hook>beforeCancelingRollback</> hook');
            $this->beforeCancelingRollback();

            throw $e;
        }

        $this->log(sprintf('<success>[OK] Rollback was successful</>'));
    }

    public function beforeStartingDeploy()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    public function beforeCancelingDeploy()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    public function beforeFinishingDeploy()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    public function beforeStartingRollback()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    public function beforeCancelingRollback()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    public function beforeFinishingRollback()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    public function initialize(Context $context): void
    {
        $this->context = $context;
        $this->logger = new Logger($context);
        $this->taskRunner = new TaskRunner($this->context->isDryRun(), $this->logger);
        $this->log('<h1>Initializing configuration</>');

        $this->log('<h2>Processing the configuration options of the deployer class</>');
        $this->config = new ConfigurationAdapter($this->configure());
        $this->log($this->config);
        $this->log('<h2>Checking technical requirements</>');
        $this->checkRequirements();
    }

    abstract protected function getConfigBuilder();

    abstract protected function configure();

    final protected function getContext(): Context
    {
        return $this->context;
    }

    final protected function getServers(): ServerRepository
    {
        return $this->config->get('servers');
    }

    final protected function log(string $message): void
    {
        $this->logger->log($message);
    }

    final protected function runLocal(string $command): TaskCompleted
    {
        $task = new Task([$this->getContext()->getLocalHost()], $command, $this->getCommandEnvVars());

        return $this->taskRunner->run($task)[0];
    }

    /**
     * @return TaskCompleted[]
     */
    final protected function runRemote(string $command, array $roles = [Server::ROLE_APP]): array
    {
        $task = new Task($this->getServers()->findByRoles($roles), $command, $this->getCommandEnvVars());

        return $this->taskRunner->run($task);
    }

    final protected function runOnServer(string $command, Server $server): TaskCompleted
    {
        $task = new Task([$server], $command, $this->getCommandEnvVars());

        return $this->taskRunner->run($task)[0];
    }

    // this method checks that any file or directory that goes into "rm -rf" command is
    // relative to the project dir. This safeguard will prevent catastrophic errors
    // related to removing the wrong file or directory on the server.
    final protected function safeDelete(Server $server, array $absolutePaths): void
    {
        $deployDir = $server->get(Property::deploy_dir);
        $pathsToDelete = [];
        foreach ($absolutePaths as $path) {
            if (Str::startsWith($path, $deployDir)) {
                $pathsToDelete[] = $path;
            } else {
                $this->log(sprintf('Skipping the unsafe deletion of "%s" because it\'s not relative to the project directory.', $path));
            }
        }

        if (empty($pathsToDelete)) {
            $this->log('There are no paths to delete.');
        }

        $this->runOnServer(sprintf('rm -rf %s', implode(' ', $pathsToDelete)), $server);
    }

    private function getCommandEnvVars(): array
    {
        $symfonyEnvironment = $this->getConfig(Option::symfonyEnvironment);
        $symfonyEnvironmentEnvVarName = $this->getConfig('_symfonyEnvironmentEnvVarName');
        $envVars = null !== $symfonyEnvironment ? [$symfonyEnvironmentEnvVarName => $symfonyEnvironment] : [];

        return $envVars;
    }

    private function checkRequirements(): void
    {
        /** @var AbstractRequirement[] $requirements */
        $requirements = $this->getRequirements();

        if (empty($requirements)) {
            $this->logger->log('<h3>No requirements defined</>');
        }

        foreach ($requirements as $requirement) {
            $this->taskRunner->run($requirement->getChecker());
            $this->log($requirement->getMessage());
        }
    }
}
