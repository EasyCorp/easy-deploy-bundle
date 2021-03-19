<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Requirement;

use EasyCorp\Bundle\EasyDeployBundle\Server\Server;
use EasyCorp\Bundle\EasyDeployBundle\Task\Task;

class CommandExists extends AbstractRequirement
{
    private $commandName;

    public function __construct(Server $server, string $commandName)
    {
        parent::__construct($server);
        $this->commandName = $commandName;
    }

    public function getMessage(): string
    {
        return sprintf('<ok>[OK]</> <command>%s</> command exists', $this->commandName);
    }

    public function getChecker(): Task
    {
        $shellCommand = sprintf('%s %s', $this->getServer()->isWindows() ? 'where' : 'which', $this->commandName);

        return new Task([$this->getServer()], $shellCommand);
    }
}
