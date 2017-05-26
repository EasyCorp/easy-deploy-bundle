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

class SymfonyVersionException extends \RuntimeException
{
    public function __construct(int $version)
    {
        parent::__construct(sprintf('The application uses the unsupported Symfony "%d" version.', $version));
    }
}
