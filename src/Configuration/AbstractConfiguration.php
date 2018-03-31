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

use EasyCorp\Bundle\EasyDeployBundle\Exception\InvalidConfigurationException;
use EasyCorp\Bundle\EasyDeployBundle\Server\Property;
use EasyCorp\Bundle\EasyDeployBundle\Server\Server;
use EasyCorp\Bundle\EasyDeployBundle\Server\ServerRepository;

/**
 * It implements the "Builder" pattern to define the configuration of the deployer.
 * This is the base builder extended by the specific builder used by each deployer.
 */
abstract class AbstractConfiguration
{
    private const RESERVED_SERVER_PROPERTIES = [Property::use_ssh_agent_forwarding];
    protected $servers;
    protected $useSshAgentForwarding = true;

    public function __construct()
    {
        $this->servers = new ServerRepository();
    }

    public function server(string $sshDsn, array $roles = [Server::ROLE_APP], array $properties = [])
    {
        $reservedProperties = array_merge(self::RESERVED_SERVER_PROPERTIES, $this->getReservedServerProperties());
        $reservedPropertiesUsed = array_intersect($reservedProperties, array_keys($properties));
        if (!empty($reservedPropertiesUsed)) {
            throw new InvalidConfigurationException(sprintf('These properties set for the "%s" server are reserved: %s. Use different property names.', $sshDsn, implode(', ', $reservedPropertiesUsed)));
        }

        $this->servers->add(new Server($sshDsn, $roles, $properties));
    }

    public function useSshAgentForwarding(bool $useIt)
    {
        $this->useSshAgentForwarding = $useIt;
    }

    abstract protected function getReservedServerProperties(): array;
}
