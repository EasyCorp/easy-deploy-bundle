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

use EasyCorp\Bundle\EasyDeployBundle\Exception\ServerConfigurationException;
use EasyCorp\Bundle\EasyDeployBundle\Helper\Str;
use Symfony\Component\HttpFoundation\ParameterBag;

class Server
{
    const ROLE_APP = 'app';
    private const LOCALHOST_ADDRESSES = ['localhost', 'local', '127.0.0.1'];
    private $roles;
    private $user;
    private $host;
    private $port;
    private $properties;

    public function __construct(string $dsn, array $roles = [self::ROLE_APP], array $properties = [])
    {
        $this->roles = $roles;
        $this->properties = new ParameterBag($properties);

        // add the 'ssh://' scheme so the URL parsing works as expected
        $params = parse_url(Str::startsWith($dsn, 'ssh://') ? $dsn : 'ssh://'.$dsn);

        $this->user = $params['user'] ?? null;

        if (!isset($params['host'])) {
            throw new ServerConfigurationException($dsn, 'The host is missing (define it as an IP address or a host name)');
        }
        $this->host = $params['host'];

        $this->port = $params['port'] ?? null;
    }

    public function __toString(): string
    {
        return sprintf('%s%s', $this->getUser() ? $this->getUser().'@' : '', $this->getHost());
    }

    public function isLocalHost(): bool
    {
        return in_array($this->getHost(), self::LOCALHOST_ADDRESSES, true);
    }

    public function resolveProperties(string $expression): string
    {
        $definedProperties = $this->properties;
        $resolved = preg_replace_callback('/(\{\{\s*(?<propertyName>.+)\s*\}\})/U', function (array $matches) use ($definedProperties, $expression) {
            $propertyName = trim($matches['propertyName']);
            if (!$definedProperties->has($propertyName)) {
                throw new \InvalidArgumentException(sprintf('The "%s" property in "%s" expression is not a valid server property.', $propertyName, $expression));
            }

            return $definedProperties->get($propertyName);
        }, $expression);

        return $resolved;
    }

    public function getProperties(): array
    {
        return $this->properties->all();
    }

    public function get(string $propertyName, $default = null)
    {
        return $this->properties->get($propertyName, $default);
    }

    public function set(string $propertyName, $value): void
    {
        $this->properties->set($propertyName, $value);
    }

    public function has(string $propertyName): bool
    {
        return $this->properties->has($propertyName);
    }

    public function getSshConnectionString(): string
    {
        if ($this->isLocalHost()) {
            return '';
        }

        return sprintf('ssh %s%s%s%s',
            $this->properties->get('use_ssh_agent_forwarding') ? '-A ' : '',
            $this->user ?? '',
            $this->user ? '@'.$this->host : $this->host,
            $this->port ? ' -p '.$this->port : ''
        );
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }
}
