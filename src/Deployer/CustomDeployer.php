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

use EasyCorp\Bundle\EasyDeployBundle\Configuration\CustomConfiguration;
use EasyCorp\Bundle\EasyDeployBundle\Requirement\AllowsLoginViaSsh;
use EasyCorp\Bundle\EasyDeployBundle\Requirement\CommandExists;

/**
 * Used when the deployment process is completely customized. Nothing is done or
 * executed for you, but you can leverage the SSH toolkit to run commands on
 * remote servers. It's similar to using Python's Fabric.
 */
abstract class CustomDeployer extends AbstractDeployer
{
    public function getConfigBuilder(): CustomConfiguration
    {
        return new CustomConfiguration();
    }

    public function getRequirements(): array
    {
        return [
            new CommandExists([$this->getContext()->getLocalHost()], 'ssh'),
            new AllowsLoginViaSsh($this->getServers()->findAll()),
        ];
    }
}
