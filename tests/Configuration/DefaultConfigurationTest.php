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

use EasyCorp\Bundle\EasyDeployBundle\Configuration\DefaultConfiguration;
use PHPUnit\Framework\TestCase;

class DefaultConfigurationTest extends TestCase
{
    /**
     * @expectedException \EasyCorp\Bundle\EasyDeployBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessageRegExp  /The repository URL must use the SSH syntax instead of the HTTPs syntax to make it work on any remote server. Replace "https:\/\/.*" by "git@.*"/
     */
    public function test_repository_url_protocol()
    {
        (new DefaultConfiguration(__DIR__))
            ->repositoryUrl('https://github.com/symfony/symfony-demo.git')
        ;
    }

    /**
     * @expectedException \EasyCorp\Bundle\EasyDeployBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The value of resetOpCacheFor option must be the valid URL of your homepage (it must start with http:// or https://).
     */
    public function test_reset_opcache_for()
    {
        (new DefaultConfiguration(__DIR__))
            ->resetOpCacheFor('symfony.com')
        ;
    }
}
