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
    private const CONFIG_DIR = '%s/etc';

    public static function guess(string $projectDir, string $stage): string
    {
        if (is_dir($confifDir = sprintf(self::CONFIG_DIR, $projectDir))) {
            return $confifDir.sprintf('/%s/deploy.php', $stage);
        }

        if (is_dir($confifDir = sprintf(self::LEGACY_CONFIG_DIR, $projectDir))) {
            return $confifDir.sprintf('/deploy_%s.php', $stage);
        }

        throw new \RuntimeException(sprintf('No default config dir found. Looked for %s and %s.', self::CONFIG_DIR, self::LEGACY_CONFIG_DIR));
    }
}
