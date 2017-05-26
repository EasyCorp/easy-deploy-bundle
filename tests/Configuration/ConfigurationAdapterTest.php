<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Tests;

use EasyCorp\Bundle\EasyDeployBundle\Configuration\ConfigurationAdapter;
use EasyCorp\Bundle\EasyDeployBundle\Configuration\DefaultConfiguration;
use EasyCorp\Bundle\EasyDeployBundle\Configuration\Option;
use PHPUnit\Framework\TestCase;

class ConfigurationAdapterTest extends TestCase
{
    /** @var DefaultConfiguration */
    private $config;

    protected function setUp()
    {
        $this->config = (new DefaultConfiguration(__DIR__))
            ->sharedFilesAndDirs([])
            ->server('host1')
            ->repositoryUrl('git@github.com:symfony/symfony-demo.git')
            ->repositoryBranch('staging')
            ->deployDir('/var/www/symfony-demo')
        ;
    }

    public function test_get_options()
    {
        $config = new ConfigurationAdapter($this->config);

        $this->assertSame('host1', (string) $config->get(Option::servers)->findAll()[0]);
        $this->assertSame('git@github.com:symfony/symfony-demo.git', $config->get(Option::repositoryUrl));
        $this->assertSame('staging', $config->get(Option::repositoryBranch));
        $this->assertSame('/var/www/symfony-demo', $config->get(Option::deployDir));
    }
}
