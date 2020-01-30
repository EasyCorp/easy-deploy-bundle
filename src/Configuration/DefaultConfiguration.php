<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Configuration;

use EasyCorp\Bundle\EasyDeployBundle\Exception\InvalidConfigurationException;
use EasyCorp\Bundle\EasyDeployBundle\Helper\Str;
use EasyCorp\Bundle\EasyDeployBundle\Server\Property;
use EasyCorp\Bundle\EasyDeployBundle\Server\Server;
use Symfony\Component\HttpKernel\Kernel;

/**
 * It implements the "Builder" pattern to define the configuration of the
 * default deployer using a fluent interface and enabling the IDE autocompletion.
 */
final class DefaultConfiguration extends AbstractConfiguration
{
    // variables starting with an underscore are for internal use only
    private $_symfonyEnvironmentEnvVarName; // SYMFONY_ENV or APP_ENV

    // properties are defined as private so the developer doesn't see them when using
    // their IDE autocompletion. To simplify things, the builder defines setter
    // methods named the same as each option.
    private $symfonyEnvironment = 'prod';
    private $keepReleases = 5;
    private $repositoryUrl;
    private $repositoryBranch = 'master';
    private $remotePhpBinaryPath = 'php';
    private $updateRemoteComposerBinary = false;
    private $remoteComposerBinaryPath = '/usr/local/bin/composer';
    private $composerInstallFlags = '--no-dev --prefer-dist --no-interaction --quiet';
    private $composerOptimizeFlags = '--optimize';
    private $installWebAssets = true;
    private $dumpAsseticAssets = false;
    private $warmupCache = true;
    private $consoleBinaryPath;
    private $localProjectDir;
    private $binDir;
    private $configDir;
    private $cacheDir;
    private $deployDir;
    private $logDir;
    private $srcDir;
    private $templatesDir;
    private $webDir;
    private $controllersToRemove = [];
    private $writableDirs = [];
    private $permissionMethod = 'chmod';
    private $permissionMode = '0777';
    private $permissionUser;
    private $permissionGroup;
    private $sharedFiles = [];
    private $sharedDirs = [];
    private $resetOpCacheFor;

    public function __construct(string $localProjectDir)
    {
        parent::__construct();
        $this->localProjectDir = $localProjectDir;
        $this->setDefaultConfiguration(Kernel::MAJOR_VERSION, Kernel::MINOR_VERSION);
    }

    // this proxy method is needed because the autocompletion breaks
    // if the parent method is used directly
    public function server(string $sshDsn, array $roles = [Server::ROLE_APP], array $properties = []): self
    {
        parent::server($sshDsn, $roles, $properties);

        return $this;
    }

    // this proxy method is needed because the autocompletion breaks
    // if the parent method is used directly
    public function useSshAgentForwarding(bool $useIt): self
    {
        parent::useSshAgentForwarding($useIt);

        return $this;
    }

    public function symfonyEnvironment(string $name): self
    {
        $this->symfonyEnvironment = $name;

        return $this;
    }

    public function keepReleases(int $numReleases): self
    {
        $this->keepReleases = $numReleases;

        return $this;
    }

    public function repositoryUrl(string $url): self
    {
        // SSH agent forwarding only works when using SSH URLs, not https URLs. Check these URLs:
        //   https://github.com/<user>/<repo>
        //   https://bitbucket.org/<user>/<repo>
        //   https://gitlab.com/<user>/<repo>.git
        if (Str::startsWith($url, 'http://') || Str::startsWith($url, 'https://')) {
            $sshUrl = preg_replace('/https?:\/\/(?<server>.*)\/(?<vendor>.*)\/(?<repository>.*)/', 'git@$1:$2/$3', $url);
            if (!Str::endsWith($sshUrl, '.git')) {
                $sshUrl .= '.git';
            }

            throw new InvalidConfigurationException(sprintf('The repository URL must use the SSH syntax instead of the HTTPs syntax to make it work on any remote server. Replace "%s" by "%s"', $url, $sshUrl));
        }

        $this->repositoryUrl = $url;

        return $this;
    }

    public function repositoryBranch(string $branchName): self
    {
        $this->repositoryBranch = $branchName;

        return $this;
    }

    public function remotePhpBinaryPath(string $path): self
    {
        $this->remotePhpBinaryPath = $path;

        return $this;
    }

    public function updateRemoteComposerBinary(bool $updateBeforeInstall): self
    {
        $this->updateRemoteComposerBinary = $updateBeforeInstall;

        return $this;
    }

    public function remoteComposerBinaryPath(string $path): self
    {
        $this->remoteComposerBinaryPath = $path;

        return $this;
    }

    public function composerInstallFlags(string $flags): self
    {
        $this->composerInstallFlags = $flags;

        return $this;
    }

    public function composerOptimizeFlags(string $flags): self
    {
        $this->composerOptimizeFlags = $flags;

        return $this;
    }

    public function installWebAssets(bool $install): self
    {
        $this->installWebAssets = $install;

        return $this;
    }

    public function dumpAsseticAssets(bool $dump): self
    {
        $this->dumpAsseticAssets = $dump;

        return $this;
    }

    public function warmupCache(bool $warmUp): self
    {
        $this->warmupCache = $warmUp;

        return $this;
    }

    public function consoleBinaryPath(string $path): self
    {
        $this->consoleBinaryPath = $path;

        return $this;
    }

    // Relative to the project root directory
    public function binDir(string $path): self
    {
        $this->validatePathIsRelativeToProject($path, __METHOD__);
        $this->binDir = rtrim($path, '/');

        return $this;
    }

    // Relative to the project root directory
    public function configDir(string $path): self
    {
        $this->validatePathIsRelativeToProject($path, __METHOD__);
        $this->configDir = rtrim($path, '/');

        return $this;
    }

    // Relative to the project root directory
    public function cacheDir(string $path): self
    {
        $this->validatePathIsRelativeToProject($path, __METHOD__);
        $this->cacheDir = rtrim($path, '/');

        return $this;
    }

    public function deployDir(string $path): self
    {
        $this->deployDir = rtrim($path, '/');

        return $this;
    }

    // Relative to the project root directory
    public function logDir(string $path): self
    {
        $this->validatePathIsRelativeToProject($path, __METHOD__);
        $this->logDir = rtrim($path, '/');

        return $this;
    }

    // Relative to the project root directory
    public function srcDir(string $path): self
    {
        $this->validatePathIsRelativeToProject($path, __METHOD__);
        $this->srcDir = rtrim($path, '/');

        return $this;
    }

    // Relative to the project root directory
    public function templatesDir(string $path): self
    {
        $this->validatePathIsRelativeToProject($path, __METHOD__);
        $this->templatesDir = rtrim($path, '/');

        return $this;
    }

    // Relative to the project root directory
    public function webDir(string $path): self
    {
        $this->validatePathIsRelativeToProject($path, __METHOD__);
        $this->webDir = rtrim($path, '/');

        return $this;
    }

    // Relative to the project root directory
    // the $paths can be glob() patterns, so this method needs to resolve them
    public function controllersToRemove(array $paths): self
    {
        $absoluteGlobPaths = array_map(function ($globPath) {
            return $this->localProjectDir.DIRECTORY_SEPARATOR.$globPath;
        }, $paths);

        $localAbsolutePaths = [];
        foreach ($absoluteGlobPaths as $path) {
            $localAbsolutePaths = array_merge($localAbsolutePaths, glob($path));
        }

        $localRelativePaths = array_map(function ($absolutePath) {
            $relativePath = str_replace($this->localProjectDir, '', $absolutePath);
            $this->validatePathIsRelativeToProject($relativePath, 'controllersToRemove');

            return trim($relativePath, DIRECTORY_SEPARATOR);
        }, $localAbsolutePaths);

        $this->controllersToRemove = $localRelativePaths;

        return $this;
    }

    // Relative to the project root directory
    public function writableDirs(array $paths): self
    {
        foreach ($paths as $path) {
            $this->validatePathIsRelativeToProject($path, __METHOD__);
        }
        $this->writableDirs = $paths;

        return $this;
    }

    public function fixPermissionsWithChmod(string $mode = '0777'): self
    {
        $this->permissionMethod = 'chmod';
        $this->permissionMode = $mode;

        return $this;
    }

    public function fixPermissionsWithChown(string $webServerUser): self
    {
        $this->permissionMethod = 'chown';
        $this->permissionUser = $webServerUser;

        return $this;
    }

    public function fixPermissionsWithChgrp(string $webServerGroup): self
    {
        $this->permissionMethod = 'chgrp';
        $this->permissionGroup = $webServerGroup;

        return $this;
    }

    public function fixPermissionsWithAcl(string $webServerUser): self
    {
        $this->permissionMethod = 'acl';
        $this->permissionUser = $webServerUser;

        return $this;
    }

    // Relative to the project root directory
    public function sharedFilesAndDirs(array $paths = []): self
    {
        $this->sharedDirs = [];
        $this->sharedFiles = [];

        foreach ($paths as $path) {
            $this->validatePathIsRelativeToProject($path, __METHOD__);
            if (is_dir($this->localProjectDir.DIRECTORY_SEPARATOR.$path)) {
                $this->sharedDirs[] = rtrim($path, DIRECTORY_SEPARATOR);
            } else {
                $this->sharedFiles[] = $path;
            }
        }

        return $this;
    }

    // the $homepageUrl (e.g. 'https://symfony.com') is needed because OPcache contents can't
    // be deleted from the terminal and deployer must make a HTTP request to a real website URL
    public function resetOpCacheFor(string $homepageUrl): self
    {
        if (!Str::startsWith($homepageUrl, 'http')) {
            throw new InvalidConfigurationException(sprintf('The value of %s option must be the valid URL of your homepage (it must start with http:// or https://).', Option::resetOpCacheFor));
        }

        $this->resetOpCacheFor = rtrim($homepageUrl, '/');

        return $this;
    }

    protected function getReservedServerProperties(): array
    {
        return [Property::bin_dir, Property::config_dir, Property::console_bin, Property::cache_dir, Property::deploy_dir, Property::log_dir, Property::src_dir, Property::templates_dir, Property::web_dir];
    }

    private function setDefaultConfiguration(int $symfonyMajorVersion, $symfonyMinorVersion): void
    {
        if (2 === $symfonyMajorVersion) {
            $this->_symfonyEnvironmentEnvVarName = 'SYMFONY_ENV';
            $this->setDirs('app', 'app/config', 'app/cache', 'app/logs', 'src', 'app/Resources/views', 'web');
            $this->controllersToRemove(['web/app_*.php']);
            $this->sharedFiles = ['app/config/parameters.yml'];
            $this->sharedDirs = ['app/logs'];
            $this->writableDirs = ['app/cache/', 'app/logs/'];
            $this->dumpAsseticAssets = true;
        } elseif (3 === $symfonyMajorVersion && 4 < $symfonyMinorVersion) {
            $this->_symfonyEnvironmentEnvVarName = 'SYMFONY_ENV';
            $this->setDirs('bin', 'app/config', 'var/cache', 'var/logs', 'src', 'app/Resources/views', 'web');
            $this->controllersToRemove(['web/app_*.php']);
            $this->sharedFiles = ['app/config/parameters.yml'];
            $this->sharedDirs = ['var/logs'];
            $this->writableDirs = ['var/cache/', 'var/logs/'];
        } elseif (4 <= $symfonyMajorVersion || (3 === $symfonyMajorVersion && 4 >= $symfonyMinorVersion)) {
            $this->_symfonyEnvironmentEnvVarName = 'APP_ENV';
            $this->setDirs('bin', 'config', 'var/cache', 'var/log', 'src', 'templates', 'public');
            $this->controllersToRemove([]);
            $this->sharedDirs = ['var/log'];
            $this->writableDirs = ['var/cache/', 'var/log/'];
        }
    }

    private function setDirs(string $binDir, string $configDir, string $cacheDir, string $logDir, string $srcDir, string $templatesDir, string $webDir): void
    {
        $this->binDir = $binDir;
        $this->configDir = $configDir;
        $this->cacheDir = $cacheDir;
        $this->logDir = $logDir;
        $this->srcDir = $srcDir;
        $this->templatesDir = $templatesDir;
        $this->webDir = $webDir;
    }

    private function validatePathIsRelativeToProject($path, $methodName): void
    {
        if (!is_readable($this->localProjectDir.DIRECTORY_SEPARATOR.$path)) {
            throw new InvalidConfigurationException(sprintf('The "%s" value given in %s() is not relative to the project root directory or is not readable.', $path, $methodName));
        }
    }
}
