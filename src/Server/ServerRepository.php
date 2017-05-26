<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Server;

/**
 * It implements the "Repository" pattern to store the servers involved in the
 * deployment and provide some helper methods to find and filter those servers.
 */
class ServerRepository
{
    /** @var Server[] $servers */
    private $servers = [];

    public function __toString(): string
    {
        return implode(', ', $this->servers);
    }

    public function add(Server $server): void
    {
        $this->servers[] = $server;
    }

    public function findAll(): array
    {
        return $this->servers;
    }

    /**
     * @return Server[]
     */
    public function findByRoles(array $roles): array
    {
        return array_filter($this->servers, function (Server $server) use ($roles) {
            return !empty(array_intersect($roles, $server->getRoles()));
        });
    }
}
