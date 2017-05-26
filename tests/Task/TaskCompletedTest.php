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

use EasyCorp\Bundle\EasyDeployBundle\Server\Server;
use EasyCorp\Bundle\EasyDeployBundle\Task\TaskCompleted;
use PHPUnit\Framework\TestCase;

class TaskCompletedTest extends TestCase
{
    public function test_server()
    {
        $result = new TaskCompleted(new Server('deployer@host1'), 'aaa', 0);

        $this->assertSame('deployer', $result->getServer()->getUser());
        $this->assertSame('host1', $result->getServer()->getHost());
    }

    public function test_output()
    {
        $result = new TaskCompleted(new Server('localhost'), 'aaa   ', 0);

        $this->assertSame('aaa   ', $result->getOutput());
        $this->assertSame('aaa', $result->getTrimmedOutput());
    }

    public function test_exit_code()
    {
        $result = new TaskCompleted(new Server('localhost'), 'aaa', -1);

        $this->assertSame(-1, $result->getExitCode());
        $this->assertFalse($result->isSuccessful());
    }
}
