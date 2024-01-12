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
use EasyCorp\Bundle\EasyDeployBundle\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

class DefaultConfigurationTest extends TestCase
{
    /**
     * @dataProvider provideHttpRepositoryUrls
     */
    public function test_repository_url_protocol(string $url)
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/The repository URL must use the SSH syntax instead of the HTTPs syntax to make it work on any remote server. Replace "https?:\/\/.*\/symfony\/symfony-demo.git" by "git@.*:symfony\/symfony-demo.git"/');

        (new DefaultConfiguration(__DIR__))
            ->repositoryUrl($url)
        ;
    }

    public function test_reset_opcache_for()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value of resetOpCacheFor option must be the valid URL of your homepage (it must start with http:// or https://).');

        (new DefaultConfiguration(__DIR__))
            ->resetOpCacheFor('symfony.com')
        ;
    }

    public function provideHttpRepositoryUrls(): \Generator
    {
        yield ['http://github.com/symfony/symfony-demo.git'];
        yield ['https://github.com/symfony/symfony-demo.git'];
        yield ['http://bitbucket.org/symfony/symfony-demo.git'];
        yield ['https://bitbucket.org/symfony/symfony-demo.git'];
        yield ['http://gitlab.com/symfony/symfony-demo.git'];
        yield ['https://gitlab.com/symfony/symfony-demo.git'];
    }
}
