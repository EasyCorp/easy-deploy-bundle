<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Deployer;

use EasyCorp\Bundle\EasyDeployBundle\Configuration\DefaultConfiguration;
use EasyCorp\Bundle\EasyDeployBundle\Configuration\Option;
use EasyCorp\Bundle\EasyDeployBundle\Exception\InvalidConfigurationException;
use EasyCorp\Bundle\EasyDeployBundle\Requirement\AllowsLoginViaSsh;
use EasyCorp\Bundle\EasyDeployBundle\Requirement\CommandExists;
use EasyCorp\Bundle\EasyDeployBundle\Server\Property;
use EasyCorp\Bundle\EasyDeployBundle\Server\Server;
use EasyCorp\Bundle\EasyDeployBundle\Task\TaskCompleted;

abstract class DefaultDeployer extends AbstractDeployer
{
    private $remoteProjectDirHasBeenCreated = false;
    private $remoteSymLinkHasBeenCreated = false;

    public function getConfigBuilder(): DefaultConfiguration
    {
        return new DefaultConfiguration($this->getContext()->getLocalProjectRootDir());
    }

    public function getRequirements(): array
    {
        $requirements = [];
        $localhost = $this->getContext()->getLocalHost();
        $allServers = $this->getServers()->findAll();
        $appServers = $this->getServers()->findByRoles([Server::ROLE_APP]);

        $requirements[] = new CommandExists([$localhost], 'git');
        $requirements[] = new CommandExists([$localhost], 'ssh');

        $requirements[] = new AllowsLoginViaSsh($allServers);
        $requirements[] = new CommandExists($appServers, $this->getConfig(Option::remoteComposerBinaryPath));
        if ('acl' === $this->getConfig(Option::permissionMethod)) {
            $requirements[] = new CommandExists($appServers, 'setfacl');
        }

        return $requirements;
    }

    final public function deploy(): void
    {
        $this->initializeServerOptions();
        $this->createRemoteDirectoryLayout();
        $this->remoteProjectDirHasBeenCreated = true;

        $this->log('Executing <hook>beforeUpdating</> hook');
        $this->beforeUpdating();
        $this->log('<h1>Updating app code</>');
        $this->doUpdateCode();

        $this->log('Executing <hook>beforePreparing</> hook');
        $this->beforePreparing();
        $this->log('<h1>Preparing app</>');
        $this->doCreateCacheDir();
        $this->doCreateLogDir();
        $this->doCreateSharedDirs();
        $this->doCreateSharedFiles();
        $this->doSetPermissions();
        $this->doInstallDependencies();
        $this->doInstallWebAssets();
        $this->doDumpAsseticAssets();

        $this->log('Executing <hook>beforeOptimizing</> hook');
        $this->beforeOptimizing();
        $this->log('<h1>Optimizing app</>');
        $this->doWarmupCache();
        $this->doClearControllers();
        $this->doOptimizeComposer();

        $this->log('Executing <hook>beforePublishing</> hook');
        $this->beforePublishing();
        $this->log('<h1>Publishing app</>');
        $this->doCreateSymlink();
        $this->remoteSymLinkHasBeenCreated = true;
        $this->doResetOpCache();
        $this->doKeepReleases();
    }

    final public function cancelDeploy(): void
    {
        if (!$this->remoteSymLinkHasBeenCreated && !$this->remoteProjectDirHasBeenCreated) {
            $this->log('<h2>No changes need to be reverted on remote servers (neither the remote project dir nor the symlink were created)</>');
        }

        if ($this->remoteSymLinkHasBeenCreated) {
            $this->doSymlinkToPreviousRelease();
        }

        if ($this->remoteProjectDirHasBeenCreated) {
            $this->doDeleteLastReleaseDirectory();
        }
    }

    final public function rollback(): void
    {
        $this->initializeServerOptions();

        $this->log('Executing <hook>beforeRollingBack</> hook');
        $this->beforeRollingBack();

        $this->doCheckPreviousReleases();
        $this->doSymlinkToPreviousRelease();
        $this->doDeleteLastReleaseDirectory();
    }

    public function beforeUpdating()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    public function beforePreparing()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    public function beforeOptimizing()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    public function beforePublishing()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    public function beforeRollingBack()
    {
        $this->log('<h3>Nothing to execute</>');
    }

    private function doCheckPreviousReleases(): void
    {
        $this->log('<h2>Getting the previous releases dirs</>');
        $results = $this->runRemote('ls -r1 {{ deploy_dir }}/releases');

        if ($this->getContext()->isDryRun()) {
            return;
        }

        foreach ($results as $result) {
            $numReleases = count(array_filter(explode("\n", $result->getOutput())));

            if ($numReleases < 2) {
                throw new \RuntimeException(sprintf('The application cannot be rolled back because the "%s" server has only 1 release and it\'s not possible to roll back to a previous version.', $result->getServer()));
            }
        }
    }

    private function doSymlinkToPreviousRelease(): void
    {
        $this->log('<h2>Reverting the current symlink to the previous version</>');
        $this->runRemote('export _previous_release_dirname=$(ls -r1 {{ deploy_dir }}/releases | head -n 2 | tail -n 1) && rm -f {{ deploy_dir }}/current && ln -s {{ deploy_dir }}/releases/$_previous_release_dirname {{ deploy_dir }}/current');
    }

    private function doDeleteLastReleaseDirectory(): void
    {
        // this is needed to avoid rolling back in the future to this version
        $this->log('<h2>Deleting the last release directory</>');
        $this->runRemote('export _last_release_dirname=$(ls -r1 {{ deploy_dir }}/releases | head -n 1) && rm -fr {{ deploy_dir }}/releases/$_last_release_dirname');
    }

    private function initializeServerOptions(): void
    {
        $this->log('<h2>Initializing server options</>');

        /** @var Server[] $allServers */
        $allServers = array_merge([$this->getContext()->getLocalHost()], $this->getServers()->findAll());
        foreach ($allServers as $server) {
            if (true === $this->getConfig(Option::useSshAgentForwarding)) {
                $this->log(sprintf('<h3>Enabling SSH agent forwarding for <server>%s</> server</>', $server));
            }
            $server->set(Property::use_ssh_agent_forwarding, $this->getConfig(Option::useSshAgentForwarding));
        }

        $appServers = $this->getServers()->findByRoles([Server::ROLE_APP]);
        foreach ($appServers as $server) {
            $this->log(sprintf('<h3>Setting the %s property for <server>%s</> server</>', Property::deploy_dir, $server));
            $server->set(Property::deploy_dir, $this->getConfig(Option::deployDir));
        }
    }

    private function initializeDirectoryLayout(Server $server): void
    {
        $this->log('<h2>Initializing server directory layout</>');

        $remoteProjectDir = $server->get(Property::project_dir);
        $server->set(Property::bin_dir, sprintf('%s/%s', $remoteProjectDir, $this->getConfig(Option::binDir)));
        $server->set(Property::config_dir, sprintf('%s/%s', $remoteProjectDir, $this->getConfig(Option::configDir)));
        $server->set(Property::cache_dir, sprintf('%s/%s', $remoteProjectDir, $this->getConfig(Option::cacheDir)));
        $server->set(Property::log_dir, sprintf('%s/%s', $remoteProjectDir, $this->getConfig(Option::logDir)));
        $server->set(Property::src_dir, sprintf('%s/%s', $remoteProjectDir, $this->getConfig(Option::srcDir)));
        $server->set(Property::templates_dir, sprintf('%s/%s', $remoteProjectDir, $this->getConfig(Option::templatesDir)));
        $server->set(Property::web_dir, sprintf('%s/%s', $remoteProjectDir, $this->getConfig(Option::webDir)));

        // this is needed because some projects use a binary directory different than the default one of their Symfony version
        $server->set(Property::console_bin, sprintf('%s %s/console', $this->getConfig(Option::remotePhpBinaryPath), $this->getConfig(Option::binDir) ? $server->get(Property::bin_dir) : $this->findConsoleBinaryPath($server)));
    }

    // this is needed because it's common for Smyfony projects to use binary directories
    // different from their Symfony version. For example: Symfony 2 projects that upgrade
    // to Symfony 3 but still use app/console instead of bin/console
    private function findConsoleBinaryPath(Server $server): string
    {
        $symfonyConsoleBinaries = ['{{ project_dir }}/app/console', '{{ project_dir }}/bin/console'];
        foreach ($symfonyConsoleBinaries as $consoleBinary) {
            $localConsoleBinary = $this->getContext()->getLocalHost()->resolveProperties($consoleBinary);
            if (is_executable($localConsoleBinary)) {
                return $server->resolveProperties($consoleBinary);
            }
        }

        if (null === $server->get(Property::console_bin)) {
            throw new InvalidConfigurationException(sprintf('The "console" binary of your Symfony application is not available in any of the following directories: %s. Configure the "binDir" option and set it to the directory that contains the "console" binary.', implode(', ', $symfonyConsoleBinaries)));
        }
    }

    private function createRemoteDirectoryLayout(): void
    {
        $this->log('<h2>Creating the remote directory layout</>');
        $this->runRemote('mkdir -p {{ deploy_dir }} && mkdir -p {{ deploy_dir }}/releases && mkdir -p {{ deploy_dir }}/shared');

        /** @var TaskCompleted[] $results */
        $results = $this->runRemote('export _release_path="{{ deploy_dir }}/releases/$(date +%Y%m%d%H%M%S)" && mkdir -p $_release_path && echo $_release_path');
        foreach ($results as $result) {
            $remoteProjectDir = $this->getContext()->isDryRun() ? '(the remote project_dir)' : $result->getTrimmedOutput();
            $result->getServer()->set(Property::project_dir, $remoteProjectDir);
            $this->initializeDirectoryLayout($result->getServer());
        }
    }

    private function doGetcodeRevision(): string
    {
        $this->log('<h2>Getting the revision ID of the code repository</>');
        $result = $this->runLocal(sprintf('git ls-remote %s %s', $this->getConfig(Option::repositoryUrl), $this->getConfig(Option::repositoryBranch)));
        $revision = explode("\t", $result->getTrimmedOutput())[0];
        if ($this->getContext()->isDryRun()) {
            $revision = '(the code revision)';
        }
        $this->log(sprintf('<h3>Code revision hash = %s</>', $revision));

        return $revision;
    }

    private function doUpdateCode(): void
    {
        $repositoryRevision = $this->doGetcodeRevision();

        $this->log('<h2>Updating code base with remote_cache strategy</>');
        $this->runRemote(sprintf('if [ -d {{ deploy_dir }}/repo ]; then cd {{ deploy_dir }}/repo && git fetch -q origin && git fetch --tags -q origin && git reset -q --hard %s && git clean -q -d -x -f; else git clone -q -b %s %s {{ deploy_dir }}/repo && cd {{ deploy_dir }}/repo && git checkout -q -b deploy %s; fi', $repositoryRevision, $this->getConfig(Option::repositoryBranch), $this->getConfig(Option::repositoryUrl), $repositoryRevision));

        $this->log('<h3>Copying the updated code to the new release directory</>');
        $this->runRemote(sprintf('cp -RPp {{ deploy_dir }}/repo/* {{ project_dir }}'));
    }

    private function doCreateCacheDir(): void
    {
        $this->log('<h2>Creating cache directory</>');
        $this->runRemote('if [ -d {{ cache_dir }} ]; then rm -rf {{ cache_dir }}; fi; mkdir -p {{ cache_dir }}');
    }

    private function doCreateLogDir(): void
    {
        $this->log('<h2>Creating log directory</>');
        $this->runRemote('if [ -d {{ log_dir }} ] ; then rm -rf {{ log_dir }}; fi; mkdir -p {{ log_dir }}');
    }

    private function doCreateSharedDirs(): void
    {
        $this->log('<h2>Creating symlinks for shared directories</>');
        foreach ($this->getConfig(Option::sharedDirs) as $sharedDir) {
            $this->runRemote(sprintf('mkdir -p {{ deploy_dir }}/shared/%s', $sharedDir));
            $this->runRemote(sprintf('if [ -d {{ project_dir }}/%s ] ; then rm -rf {{ project_dir }}/%s; fi', $sharedDir, $sharedDir));
            $this->runRemote(sprintf('ln -nfs {{ deploy_dir }}/shared/%s {{ project_dir }}/%s', $sharedDir, $sharedDir));
        }
    }

    private function doCreateSharedFiles(): void
    {
        $this->log('<h2>Creating symlinks for shared files</>');
        foreach ($this->getConfig(Option::sharedFiles) as $sharedFile) {
            $sharedFileParentDir = dirname($sharedFile);
            $this->runRemote(sprintf('mkdir -p {{ deploy_dir }}/shared/%s', $sharedFileParentDir));
            $this->runRemote(sprintf('touch {{ deploy_dir }}/shared/%s', $sharedFile));
            $this->runRemote(sprintf('ln -nfs {{ deploy_dir }}/shared/%s {{ project_dir }}/%s', $sharedFile, $sharedFile));
        }
    }

    // this method was inspired by https://github.com/deployphp/deployer/blob/master/recipe/deploy/writable.php
    // (c) Anton Medvedev <anton@medv.io>
    private function doSetPermissions(): void
    {
        $permissionMethod = $this->getConfig(Option::permissionMethod);
        $writableDirs = implode(' ', $this->getConfig(Option::writableDirs));
        $this->log(sprintf('<h2>Setting permissions for writable dirs using the "%s" method</>', $permissionMethod));

        if ('chmod' === $permissionMethod) {
            $this->runRemote(sprintf('chmod -R %s %s', $this->getConfig(Option::permissionMode), $writableDirs));

            return;
        }

        if ('chown' === $permissionMethod) {
            $this->runRemote(sprintf('sudo chown -RL %s %s', $this->getConfig(Option::permissionUser), $writableDirs));

            return;
        }

        if ('chgrp' === $permissionMethod) {
            $this->runRemote(sprintf('sudo chgrp -RH %s %s', $this->getConfig(Option::permissionGroup), $writableDirs));

            return;
        }

        if ('acl' === $permissionMethod) {
            $this->runRemote(sprintf('sudo setfacl -RL -m u:"%s":rwX -m u:`whoami`:rwX %s', $this->getConfig(Option::permissionUser), $writableDirs));
            $this->runRemote(sprintf('sudo setfacl -dRL -m u:"%s":rwX -m u:`whoami`:rwX %s', $this->getConfig(Option::permissionUser), $writableDirs));

            return;
        }

        throw new InvalidConfigurationException(sprintf('The "%s" permission method is not valid. Select one of the supported methods.', $permissionMethod));
    }

    private function doInstallDependencies(): void
    {
        if (true === $this->getConfig(Option::updateRemoteComposerBinary)) {
            $this->log('<h2>Self Updating the Composer binary</>');
            $this->runRemote(sprintf('%s self-update', $this->getConfig(Option::remoteComposerBinaryPath)));
        }

        $this->log('<h2>Installing Composer dependencies</>');
        $this->runRemote(sprintf('%s install %s', $this->getConfig(Option::remoteComposerBinaryPath), $this->getConfig(Option::composerInstallFlags)));
    }

    private function doInstallWebAssets(): void
    {
        if (true !== $this->getConfig(Option::installWebAssets)) {
            return;
        }

        $this->log('<h2>Installing web assets</>');
        $this->runRemote(sprintf('{{ console_bin }} assets:install {{ web_dir }} --symlink --no-debug --env=%s', $this->getConfig(Option::symfonyEnvironment)));
    }

    private function doDumpAsseticAssets(): void
    {
        if (true !== $this->getConfig(Option::dumpAsseticAssets)) {
            return;
        }

        $this->log('<h2>Dumping Assetic assets</>');
        $this->runRemote(sprintf('{{ console_bin }} assetic:dump --no-debug --env=%s', $this->getConfig(Option::symfonyEnvironment)));
    }

    private function doWarmupCache(): void
    {
        if (true !== $this->getConfig(Option::warmupCache)) {
            return;
        }

        $this->log('<h2>Warming up cache</>');
        $this->runRemote(sprintf('{{ console_bin }} cache:warmup --no-debug --env=%s', $this->getConfig(Option::symfonyEnvironment)));
        $this->runRemote('chmod -R g+w {{ cache_dir }}');
    }

    private function doClearControllers(): void
    {
        $this->log('<h2>Clearing controllers</>');
        foreach ($this->getServers()->findByRoles([Server::ROLE_APP]) as $server) {
            $absolutePaths = array_map(function ($relativePath) use ($server) {
                return $server->resolveProperties(sprintf('{{ project_dir }}/%s', $relativePath));
            }, $this->getConfig(Option::controllersToRemove));

            $this->safeDelete($server, $absolutePaths);
        }
    }

    private function doOptimizeComposer(): void
    {
        $this->log('<h2>Optimizing Composer autoloader</>');
        $this->runRemote(sprintf('%s dump-autoload %s', $this->getConfig(Option::remoteComposerBinaryPath), $this->getConfig(Option::composerOptimizeFlags)));
    }

    private function doCreateSymlink(): void
    {
        $this->log('<h2>Updating the symlink</>');
        $this->runRemote('rm -f {{ deploy_dir }}/current && ln -s {{ project_dir }} {{ deploy_dir }}/current');
    }

    private function doResetOpCache(): void
    {
        if (null === $homepageUrl = $this->getConfig(Option::resetOpCacheFor)) {
            return;
        }

        $this->log('<h2>Resetting the OPcache contents</>');
        $phpScriptPath = sprintf('__easy_deploy_opcache_reset_%s.php', bin2hex(random_bytes(8)));
        $this->runRemote(sprintf('echo "<?php opcache_reset();" > {{ web_dir }}/%s && wget %s/%s && rm -f {{ web_dir }}/%s', $phpScriptPath, $homepageUrl, $phpScriptPath, $phpScriptPath));
    }

    private function doKeepReleases(): void
    {
        if (-1 === $this->getConfig(Option::keepReleases)) {
            $this->log('<h3>No releases to delete</>');

            return;
        }

        $results = $this->runRemote('ls -1 {{ deploy_dir }}/releases');
        foreach ($results as $result) {
            $this->deleteOldReleases($result->getServer(), explode("\n", $result->getTrimmedOutput()));
        }
    }

    private function deleteOldReleases(Server $server, array $releaseDirs): void
    {
        foreach ($releaseDirs as $releaseDir) {
            if (!preg_match('/\d{14}/', $releaseDir)) {
                $this->log(sprintf('[<server>%s</>] Skipping cleanup of old releases; unexpected "%s" directory found (all directory names should be timestamps)', $server, $releaseDir));

                return;
            }
        }

        if (count($releaseDirs) <= $this->getConfig(Option::keepReleases)) {
            $this->log(sprintf('[<server>%s</>] No releases to delete (there are %d releases and the config keeps %d releases).', $server, count($releaseDirs), $this->getConfig(Option::keepReleases)));

            return;
        }

        $relativeDirsToRemove = array_slice($releaseDirs, 0, -$this->getConfig(Option::keepReleases));
        $absoluteDirsToRemove = array_map(function ($v) {
            return sprintf('%s/releases/%s', $this->getConfig(Option::deployDir), $v);
        }, $relativeDirsToRemove);

        // the command must be run only on one server because the timestamps are
        // different for all servers, even when they belong to the same deploy and
        // because new servers may have been added to the deploy and old releases don't exist on them
        $this->log(sprintf('Deleting these old release directories: %s', implode(', ', $absoluteDirsToRemove)));
        $this->safeDelete($server, $absoluteDirsToRemove);
    }
}
