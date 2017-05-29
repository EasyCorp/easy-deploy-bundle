Default Deployer
================

This is the deployment strategy used by default. It supports any number of
remote servers and is a "rolling update", which deploys applications without any
downtime. It's based on [Capistrano][1] and [Capifony][2]. If you know any of
those, skip the first sections that explain what you need and how it works.

What You Need
-------------

* **Local Machine**:
  * A Symfony application with the EasyDeploy bundle installed.
  * A SSH client executable via the `ssh` console command.
* **Remote Server/s**:
  * A SSH server that accepts connections from your local machine.
  * The Composer binary installed.
* **Symfony Application**:
  * Code must be stored in a Git server (GitHub, BitBucket, GitLab, your own
    server) accessible from the local machine.
  * The application can use any Symfony version (2.7+, 3.x, 4.x).

How Does It Work
----------------

The deployer creates a predefined directory structure on each remote server to
store the application code and other data related to deployment. The root
directory is defined by you with the `deployDir()` option:

```php
public function configure()
{
    return $this->getConfigBuilder()
        ->deployDir('/var/www/my-project')
        // ...
    ;
}
```

Then, the following directory structure is created on each remote server:

```
/var/www/my-project/
├── current -> /var/www/my-project/releases/20170517201708/
├── releases
│   ├── 20170517200103/
│   ├── 20170517200424/
│   ├── 20170517200736/
│   ├── 20170517201502/
│   └── 20170517201708/
├── repo/
└── shared
    └── <linked_files and linked_dirs>
```

* `current` is a symlink pointing to the most recent release. The trick of this
  deployment strategy is updating the symlink at the end of a successful deployment.
  If any error happens, the symlink can be reverted to the previous working version.
  That's how this strategy achieves the zero-downtime deployments.
* `releases/` stores a configurable amount of past releases. The directory names
  are the timestamps of each release.
* `repo/` stores a copy of the application's git repository and updates it for
  each deployment (this speeds up a lot the process of getting the application code).
* `shared/` contains the files and directories configured as shared in your
  application (e.g. the "logs/" directory). These files/directories are shared
  between all releases.

EasyDeploy creates this directory structure for you. There's no need to execute
any command to setup the servers or configure anything.

### Web Server Configuration

If you start using this strategy to deploy an already existing application, you
may need to update your web server configuration. Specifically, you must update
the document root to include the `current` symlink, which always points to the
most recent version. The following example shows the changes needed for the
Apache web server configuration:

```diff
<VirtualHost *:80>
    # ...

-   DocumentRoot    /var/www/vhosts/example.com/web
+   DocumentRoot    /var/www/vhosts/example.com/current/web
    DirectoryIndex  app.php

-   <Directory /var/www/vhosts/example.com/web>
+   <Directory /var/www/vhosts/example.com/current/web>
        RewriteEngine On
        RewriteCond   %{REQUEST_FILENAME} !-f
        RewriteRule   ^(.*)$ app.php [QSA,L]
    </Directory>

    # ...
</VirtualHost>
```

Configuration
-------------

Your IDE can autocomplete all the existing config options for this deployer, so
you don't have to read this section or memorize any config option or special
syntax. However, for reference purposes, all the config options are listed below:

### Common Options

They are explained in the previous chapter about the configuration that is
common for all deployers:

  * `->server(string $sshDsn, array $roles = ['app'], array $properties = [])`
  * `->useSshAgentForwarding(bool $useIt = true)`

### Composer and PHP Options

  * `->updateRemoteComposerBinary(bool $updateBeforeInstall = false)`
  * `->remoteComposerBinaryPath(string $path = '/usr/local/bin/composer')`
  * `->composerInstallFlags(string $flags = '--no-dev --prefer-dist --no-interaction --quiet')`
  * `->composerOptimizeFlags(string $flags = '--optimize --quiet')`
  * `->remotePhpBinaryPath(string $path = 'php')` the path of the PHP command
    added to Symfony commands. By default is `php` (which means:
    `php path/to/project/bin/console`). It's useful when the server has multiple
    PHP installations (e.g. `->remotePhpBinaryPath('/usr/bin/php7.1-sp')`)

### Code Options

  * `->repositoryUrl(string $url)` (it must be a Git repository)
  * `->repositoryBranch('master')` (the exact branch to deploy; usually `master`
    for `prod`, `staging` for the `staging` servers, etc.)
  * `->deployDir(string $path = '...')` (the directory in the remote server where
    the application is deployed)

> **NOTE**
>
> Depending on your local and remote configuration, cloning the repository code
> in the remote servers may fail. Read [this tutorial][4] to learn about the
> most common ways to clone code on remote servers.

### Symfony Application Options

The Symfony environment must be chosen carefully because, by default, commands
are executed in that environment (`prod` by default):

  * `->symfonyEnvironment(string $name = 'prod')`

The default value of these options depend on the Symfony version used by your
application. Customize these options only if your application uses a directory
structure different from the default one proposed by Symfony. The values are
always relative to the project root dir:

  * `->consoleBinaryPath(string $path = '...')` (the dir where Symfony's `console`
    script is stored; e.g. `bin/`)
  * `->binDir(string $path = '...')`
  * `->configDir(string $path = '...')`
  * `->cacheDir(string $path = '...')`
  * `->logDir(string $path = '...')`
  * `->srcDir(string $path = '...')`
  * `->templatesDir(string $path = '...')`
  * `->webDir(string $path = '...')`

This option configures the files and dirs which are shared between all releases.
The values must be paths relative to the project root dir (which is usually
`kernel.root_dir/../`). Its default value depends on the Symfony version used by
your application:

  * `->sharedFilesAndDirs(array $paths = ['...'])` (by default,
    `app/config/parameters.yml` file in Symfony 2 and 3 and no file in Symfony 4;
    and the `app/logs/` dir in Symfony 2 and `var/logs/` in Symfony 3 and 4)

These options enable/disable some operations commonly executed after the
application is installed:

  * `->installWebAssets(bool $install = true)`
  * `->dumpAsseticAssets(bool $dump = false)`
  * `->warmupCache(bool $warmUp = true)`

### Security Options

  * `->controllersToRemove(array $paths = ['...'])` (values can be glob() expressions;
    by default is `app_*.php` in Symfony 2 and 3 and nothing in Symfony 4)
  * `->writableDirs(array $paths = ['...'])` (the dirs where the Symfony application
    can create files and dirs; by default, the cache/ and logs/ dirs)

These options define the method used by EasyDeploy to set the permissions of the
directories defined as "writable":

  * `->fixPermissionsWithChmod(string $mode = '0777')`
  * `->fixPermissionsWithChown(string $webServerUser)`
  * `->fixPermissionsWithChgrp(string $webServerGroup)`
  * `->fixPermissionsWithAcl(string $webServerUser)`

### Misc. Options

  * `->keepReleases(int $numReleases = 5)` (the number of past releases to keep
    when deploying a new version; if you want to roll back, this must be higher
    than `1`)
  * `->resetOpCacheFor(string $homepageUrl)` (if you use OPcache, you must reset
    it after each new deploy; however, you can't reset the OPcache contents from
    the command line; EasyDeploy uses a smart trick to reset the cache, but it
    needs to know the URL of the homepage of your application; e.g. `https://symfony.com`)

Execution Flow
--------------

In the previous chapters you learned about the "hooks", which are a way to
execute your own commands before and after the deployment/rollback processes.
The "hooks" which are common to all deployers are:

  * `public function beforeStartingDeploy()`
  * `public function beforeFinishingDeploy()`
  * `public function beforeCancelingDeploy()`
  * `public function beforeStartingRollback()`
  * `public function beforeCancelingRollback()`
  * `public function beforeFinishingRollback()`

In addition to those, the default deployer adds the following hooks:

  * `public function beforeUpdating()`, executed just before the Git repository
    is updated for the branch defined above.
  * `public function beforePreparing()`, executed just before doing the
    `composer install`, setting the permissions, installing assets, etc.
  * `public function beforeOptimizing()`, executed just before clearing controllers,
    warming up the cache and optimizing Composer.
  * `public function beforePublishing()`, executed just before changing the
    symlink to the new release.
  * `public function beforeRollingBack()`, executed just before starting the
    roll back process.

Commands
--------

The `runLocal(string $command)` and `runRemote(string $command)` methods work as
explained in the previous chapter. However, they are improved because you can
use some variables inside them:

```php
return new class extends DefaultDeployer
{
    public function configure()
    {
        // ...
    }

    public function beforeStartingDeploy()
    {
        $this->runLocal('cp {{ templates_dir }}/maintenance.html.dist {{ web_dir }}/maintenance.html');
        // equivalent to:
        // $this->runLocal('cp /path/to/project/app/Resources/views/maintenance.html.dist /path/to/project/web/maintenance.html');
    }

    public function beforeFinishingDeploy()
    {
        $this->runRemote('{{ console_bin }} app:my-task-name');
        // equivalent to:
        // $this->runRemote('php /path/to/project/bin/console app:my-task-name');
    }
}
```

These are the variables that can be used inside commands:

  * `{{ deploy_dir }}` (the same value that you configured earlier with `deployDir()`)
  * `{{ project_dir }}` (the exact directory of the current release, which is a
    timestamped directory inside `{{ deploy_dir }}/releases/`; when deploying to
    multiple servers, this directory is different on each of them, so you must
    always use this variable)
  * `{{ bin_dir }}`
  * `{{ config_dir }}`
  * `{{ cache_dir }}`
  * `{{ log_dir }}`
  * `{{ src_dir }}`
  * `{{ templates_dir }}`
  * `{{ web_dir }}`
  * `{{ console_bin }}` (the full Symfony `console` script executable; e.g.
    `php bin/console`)

Skeleton
--------

The following example shows the minimal code needed for this deployer. If your
deployment is not heavily customized, there is no need to implement any other
method besides `configure()`:

```php
use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class extends DefaultDeployer
{
    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('user@hostname')
            ->deployDir('/var/www/my-project')
            ->repositoryUrl('git@github.com:symfony/symfony-demo.git')
        ;
    }
};
```

Full Example
------------

The following example shows the full code needed to deploy the [Symfony Demo][3]
application to two remote servers, execute some quality checks before deploying
and post a message on a Slack channel when the deploy has finished:

```php
use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class extends DefaultDeployer
{
    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('deployer@123.123.123.123')
            ->server('deployer@host2.example.com')
            ->deployDir('/var/www/symfony-demo')
            ->repositoryUrl('git@github.com:symfony/symfony-demo.git')
            ->symfonyEnvironment('prod')
            ->resetOpCacheFor('https://demo.symfony.com')
        ;
    }

    public function beforeStartingDeploy()
    {
        $this->log('Checking that the repository is in a clean state.');
        $this->runLocal('git diff --quiet');

        $this->log('Running tests, linters and checkers.');
        $this->runLocal('./bin/console security:check --env=dev');
        $this->runLocal('./bin/console lint:twig app/Resources/ --no-debug');
        $this->runLocal('./bin/console lint:yaml app/ --no-debug');
        $this->runLocal('./bin/console lint:xliff app/Resources/ --no-debug');
        $this->runLocal('./vendor/bin/simple-phpunit');
    }

    public function beforeFinishingDeploy()
    {
        $slackHook = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';
        $message = json_encode(['text' => 'Application successfully deployed!']);
        $this->runLocal(sprintf("curl -X POST -H 'Content-type: application/json' --data '%s' %s", $message, $slackHook));
    }
};
```

[1]: http://capistranorb.com/
[2]: https://github.com/everzet/capifony
[3]: https://github.com/symfony/symfony-demo
[4]: tutorials/remote-code-cloning.md
