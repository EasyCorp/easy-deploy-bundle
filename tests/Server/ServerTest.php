<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\EasyDeployBundle\Tests;

use EasyCorp\Bundle\EasyDeployBundle\Server\Property;
use EasyCorp\Bundle\EasyDeployBundle\Server\Server;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    /** @dataProvider dsnProvider */
    public function test_dsn_parsing(string $dsn, string $expectedHost, ?string $expectedUser, ?int $expectedPort)
    {
        $server = new Server($dsn);

        $this->assertSame($expectedHost, $server->getHost());
        $this->assertSame($expectedUser, $server->getUser());
        $this->assertSame($expectedPort, $server->getPort());
    }

    /**
     * @expectedException \EasyCorp\Bundle\EasyDeployBundle\Exception\ServerConfigurationException
     * @expectedExceptionMessage The host is missing (define it as an IP address or a host name)
     */
    public function test_dsn_parsing_error()
    {
        new Server('deployer@');
    }

    /** @dataProvider localDsnProvider */
    public function test_local_dsn_parsing(string $dsn)
    {
        $server = new Server($dsn);

        $this->assertTrue($server->isLocalHost());
    }

    /** @dataProvider sshConnectionStringProvider */
    public function test_ssh_connection_string($dsn, $expectedSshConnectionString)
    {
        $server = new Server($dsn);

        $this->assertSame($expectedSshConnectionString, $server->getSshConnectionString());
    }

    public function test_ssh_agent_forwarding()
    {
        $server = new Server('host');
        $server->set(Property::use_ssh_agent_forwarding, true);

        $this->assertSame('ssh -A host', $server->getSshConnectionString());
    }

    public function test_default_server_roles()
    {
        $server = new Server('host');

        $this->assertSame([Server::ROLE_APP], $server->getRoles());
    }

    /** @dataProvider serverRolesProvider */
    public function test_server_roles(array $definedRoles, array $expectedRoles)
    {
        $server = new Server('host', $definedRoles);

        $this->assertSame($expectedRoles, $server->getRoles());
    }

    public function test_default_server_properties()
    {
        $server = new Server('host');

        $this->assertSame([], $server->getProperties());
    }

    public function test_server_properties()
    {
        $properties = ['prop1' => -3.14, 'prop2' => false, 'prop3' => 'Lorem Ipsum', 'prop4' => ['foo' => 'bar']];
        $server = new Server('host', [], $properties);

        $this->assertSame($properties, $server->getProperties());
    }

    public function test_get_set_has_server_properties()
    {
        $properties = ['prop1' => -3.14, 'prop2' => false, 'prop3' => 'Lorem Ipsum', 'prop4' => ['foo' => 'bar']];
        $server = new Server('host');

        foreach ($properties as $name => $value) {
            $server->set($name, $value);
        }

        foreach ($properties as $name => $value) {
            $this->assertTrue($server->has($name));
            $this->assertSame($value, $server->get($name));
        }
    }

    /** @dataProvider expressionProvider */
    public function test_resolve_properties(array $properties, string $expression, string $expectedExpression)
    {
        $server = new Server('host', [], $properties);

        $this->assertSame($expectedExpression, $server->resolveProperties($expression));
    }

    /**
     * @dataProvider wrongExpressionProvider
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /The ".*" property in ".*" expression is not a valid server property./
     */
    public function test_resolve_unknown_properties(array $properties, string $expression)
    {
        $server = new Server('host', [], $properties);
        $server->resolveProperties($expression);
    }

    public function dsnProvider()
    {
        yield ['123.123.123.123', '123.123.123.123', null, null];
        yield ['deployer@123.123.123.123', '123.123.123.123', 'deployer', null];
        yield ['deployer@123.123.123.123:22001', '123.123.123.123', 'deployer', 22001];

        yield ['example.com', 'example.com', null, null];
        yield ['deployer@example.com', 'example.com', 'deployer', null];
        yield ['deployer@example.com:22001', 'example.com', 'deployer', 22001];

        yield ['host', 'host', null, null];
        yield ['deployer@host', 'host', 'deployer', null];
        yield ['deployer@host:22001', 'host', 'deployer', 22001];

        yield ['ssh://deployer@123.123.123.123:22001', '123.123.123.123', 'deployer', 22001];
        yield ['ssh://deployer@example.com:22001', 'example.com', 'deployer', 22001];
        yield ['ssh://deployer@host:22001', 'host', 'deployer', 22001];
    }

    public function localDsnProvider()
    {
        yield ['local'];
        yield ['deployer@local'];
        yield ['deployer@local:22001'];

        yield ['localhost'];
        yield ['deployer@localhost'];
        yield ['deployer@localhost:22001'];

        yield ['127.0.0.1'];
        yield ['deployer@127.0.0.1'];
        yield ['deployer@127.0.0.1:22001'];
    }

    public function serverRolesProvider()
    {
        yield [[], []];
        yield [[Server::ROLE_APP], [Server::ROLE_APP]];
        yield [['custom_role'], ['custom_role']];
        yield [['custom_role_1', 'custom_role_2'], ['custom_role_1', 'custom_role_2']];
    }

    public function sshConnectionStringProvider()
    {
        yield ['localhost', ''];
        yield ['123.123.123.123', 'ssh 123.123.123.123'];
        yield ['deployer@123.123.123.123', 'ssh deployer@123.123.123.123'];
        yield ['deployer@123.123.123.123:22001', 'ssh deployer@123.123.123.123 -p 22001'];
    }

    public function expressionProvider()
    {
        yield [['prop1' => 'aaa'], '{{ prop1 }}', 'aaa'];
        yield [['prop.1' => 'aaa'], '{{ prop.1 }}', 'aaa'];
        yield [['prop-1' => 'aaa'], '{{ prop-1 }}', 'aaa'];
        yield [['prop-1.2_3' => 'aaa'], '{{ prop-1.2_3 }}', 'aaa'];
        yield [['prop1' => 'aaa'], '{{   prop1 }}', 'aaa'];
        yield [['prop1' => 'aaa'], '{{ prop1   }}', 'aaa'];
        yield [['prop1' => 'aaa'], '{{   prop1   }}', 'aaa'];
        yield [['prop1' => 'aaa'], '{{ prop1', '{{ prop1'];
        yield [['prop1' => 'aaa', 'prop2' => 'bbb'], 'cd {{ prop1 }} && run {{ prop2 }}', 'cd aaa && run bbb'];
        yield [['prop1' => 'aaa', 'prop2' => 'bbb'], 'cd {{ prop1 }}{{ prop2 }}', 'cd aaabbb'];
    }

    public function wrongExpressionProvider()
    {
        yield [[], '{{ prop1 }}'];
        yield [['prop1' => 'aaa'], '{{ prop 1 }}'];
        yield [['prop1' => 'aaa'], '{{ prop2 }}'];
        yield [['prop1' => 'aaa'], 'cd {{ prop1 }} && run {{ prop2 }}'];
    }
}
