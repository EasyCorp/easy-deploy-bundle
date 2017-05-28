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

/**
 * It defines the names of the configuration options for all deployers to avoid
 * using "magic strings" in the application. It's common to define PHP constants
 * in uppercase, but these are in lowercase because of how deployers are config.
 * Configuration uses autocompletion based on methods named like the options
 * (e.g. ->binDir() configures the $bindDir option). Using uppercase would
 * create ugly method names (e.g. ->BIN_DIR()).
 */
final class Option
{
    const binDir = 'binDir';
    const cacheDir = 'cacheDir';
    const composerInstallFlags = 'composerInstallFlags';
    const composerOptimizeFlags = 'composerOptimizeFlags';
    const configDir = 'configDir';
    const consoleBinaryPath = 'consoleBinaryPath';
    const context = 'context';
    const controllersToRemove = 'controllersToRemove';
    const deployDir = 'deployDir';
    const dumpAsseticAssets = 'dumpAsseticAssets';
    const installWebAssets = 'installWebAssets';
    const keepReleases = 'keepReleases';
    const logDir = 'logDir';
    const permissionMethod = 'permissionMethod';
    const permissionMode = 'permissionMode';
    const permissionUser = 'permissionUser';
    const permissionGroup = 'permissionGroup';
    const remotePhpBinaryPath = 'remotePhpBinaryPath';
    const remoteComposerBinaryPath = 'remoteComposerBinaryPath';
    const repositoryBranch = 'repositoryBranch';
    const repositoryUrl = 'repositoryUrl';
    const resetOpCacheFor = 'resetOpCacheFor';
    const servers = 'servers';
    const sharedFiles = 'sharedFiles';
    const sharedDirs = 'sharedDirs';
    const srcDir = 'srcDir';
    const symfonyEnvironment = 'symfonyEnvironment';
    const templatesDir = 'templatesDir';
    const updateRemoteComposerBinary = 'updateRemoteComposerBinary';
    const useSshAgentForwarding = 'useSshAgentForwarding';
    const warmupCache = 'warmupCache';
    const webDir = 'webDir';
    const writableDirs = 'writableDirs';
}
