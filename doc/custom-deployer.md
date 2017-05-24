Custom Deployer
===============

This is the deployment strategy that can be used when your deployment workflow
doesn't fit the one proposed by the default deployer. This strategy assumes
nothing about the deployment, so you must implement the entire deploy and
rollback workflows. It's similar to using Python's Fabric tool.

What You Need
-------------

* **Local Machine**:
  * A Symfony application with the EasyDeploy bundle installed.
  * A SSH client executable via the `ssh` console command.
* **Remote Server/s**:
  * A SSH server that accepts connections from your local machine.
* **Symfony Application**:
  * No special requirements (it can use any Symfony version and its code
    can be stored anywhere).

Configuration
-------------

The custom deployer doesn't define any configuration option, so there's only
the two options common to all deployers: `server()` to add remote servers and
`useSshAgentForwarding()`.

Execution Flow
--------------

There are no special hooks defined for this deployer, so you can use the same
hooks defined for all deployers:

  * `public function beforeStartingDeploy()`
  * `public function beforeFinishingDeploy()`
  * `public function beforeCancelingDeploy()`
  * `public function beforeStartingRollback()`
  * `public function beforeCancelingRollback()`
  * `public function beforeFinishingRollback()`

Commands
--------

There are no special commands for this deployer and there are no changes to the
default commands defined for all deployers:

  * `runLocal(string $command)`
  * `runRemote(string $command)`
  * `log(string $message)`

Skeleton
--------

The following example shows the minimal code needed for this deployer. In
addition to defining the configuration in `configure()`, you must implement
three methods (`deploy()`, `cancelDeploy()`, `rollback()`) to define the logic
of the deployment:

```php
use EasyCorp\Bundle\EasyDeployBundle\Deployer\CustomDeployer;

return new class extends CustomDeployer
{
    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('user@hostname')
        ;
    }

    public function deploy()
    {
        // ...
    }

    public function cancelDeploy()
    {
        // ...
    }

    public function rollback()
    {
        // ...
    }
};
```

Full Example
------------

The following example shows the full code needed to deploy a Symfony application
to an Amazon AWS EC2 instance using `rsync`. The rollback feature is not
implemented to not overcomplicate the example:

```php
use EasyCorp\Bundle\EasyDeployBundle\Deployer\CustomDeployer;

return new class extends CustomDeployer
{
    private $deployDir = '/var/www/my-project';

    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('user@ec2-123-123-123-123.us-west-1.compute.amazonaws.com')
        ;
    }

    public function beforeStartingDeploy()
    {
        $this->log('Checking that the repository is in a clean state.');
        $this->runLocal('git diff --quiet');

        $this->log('Preparing the app');
        $this->runLocal('rm -fr ./var/cache/*');
        $this->runLocal('SYMFONY_ENV=prod ./bin/console assets:install web/');
        $this->runLocal('SYMFONY_ENV=prod ./bin/console lint:twig app/Resources/ --no-debug');
        $this->runLocal('yarn install');
        $this->runLocal('NODE_ENV=production ./node_modules/.bin/webpack --progress');
        $this->runLocal('composer dump-autoload --optimize');
    }

    public function deploy()
    {
        $server = $this->getServers()->findAll()[0];

        $this->runRemote('cp app/Resources/views/maintenance.html web/maintenance.html');
        $this->runLocal(sprintf('rsync --progress -crDpLt --force --delete ./ %s@%s:%s', $server->getUser(), $server->getHost(), $this->deployDir));
        $this->runRemote('SYMFONY_ENV=prod sudo -u www-data bin/console cache:warmup --no-debug');
        $this->runRemote('SYMFONY_ENV=prod sudo -u www-data bin/console app:update-contents --no-debug');
        $this->runRemote('rm -rf web/maintenance.html');

        $this->runRemote('sudo restart php7.1-fpm');
    }

    public function cancelDeploy()
    {
        // ...
    }

    public function rollback()
    {
        // ...
    }
};
```
