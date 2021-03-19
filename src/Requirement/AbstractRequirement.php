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

abstract class AbstractRequirement
{
    /** @var Server */
    private $server;

    public function __construct(Server $server)
    {
        $this->server= $server;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    abstract public function getChecker(): Task;

    abstract public function getMessage(): string;
}
