<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Helper;

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
class SymfonyConfigPathGuesser
{
    private const LEGACY_CONFIG_DIR = '%s/app/config';
    private const CONFIG_DIR = '%s/config';

    public static function guess(string $projectDir, string $stage): string
    {
        if (is_dir($configDir = sprintf(self::CONFIG_DIR, $projectDir))) {
            return sprintf('%s/%s/deploy.php', $configDir, $stage);
        }

        if (is_dir($configDir = sprintf(self::LEGACY_CONFIG_DIR, $projectDir))) {
            return sprintf('%s/deploy_%s.php', $configDir, $stage);
        }

        throw new \RuntimeException(sprintf('None of the usual Symfony config dirs exist in the application. Create one of these dirs before continuing: "%s" or "%s".', self::CONFIG_DIR, self::LEGACY_CONFIG_DIR));
    }
}
