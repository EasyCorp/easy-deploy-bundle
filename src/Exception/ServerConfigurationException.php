<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Exception;

class ServerConfigurationException extends \InvalidArgumentException
{
    public function __construct(string $serverName, string $cause)
    {
        parent::__construct(sprintf('The connection string for "%s" server is wrong: %s.', $serverName, $cause));
    }
}
