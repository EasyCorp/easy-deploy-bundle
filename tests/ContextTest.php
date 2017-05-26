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

use EasyCorp\Bundle\EasyDeployBundle\Context;
use EasyCorp\Bundle\EasyDeployBundle\Server\Property;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ContextTest extends TestCase
{
    public function test_context_creates_localhost()
    {
        $context = new Context(new ArrayInput([]), new NullOutput(), __DIR__, __DIR__.'/deploy_prod.log', true, true);

        $this->assertSame('localhost', $context->getLocalHost()->getHost());
        $this->assertSame(__DIR__, $context->getLocalHost()->get(Property::project_dir));
    }
}
