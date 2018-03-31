<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Configuration;

use EasyCorp\Bundle\EasyDeployBundle\Server\Server;

/**
 * It implements the "Builder" pattern to define the configuration of the custom
 * deployer using a fluent interface and enabling the IDE autocompletion.
 */
class CustomConfiguration extends AbstractConfiguration
{
    // this proxy method is needed because the autocompletion breaks
    // if the parent method is used directly
    public function server(string $sshDsn, array $roles = [Server::ROLE_APP], array $properties = []): self
    {
        parent::server($sshDsn, $roles, $properties);

        return $this;
    }

    // this proxy method is needed because the autocompletion breaks
    // if the parent method is used directly
    public function useSshAgentForwarding(bool $useIt): self
    {
        parent::useSshAgentForwarding($useIt);

        return $this;
    }

    protected function getReservedServerProperties(): array
    {
        return [];
    }
}
