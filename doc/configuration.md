Configuration
=============

Deployment Strategies
---------------------

There are a lot of different ways to deploy a Symfony application. This bundle
provides two *deployers* that implement different strategies:

* **Default Deployer**, it's the same zero-downtime strategy implemented by
  tools like Capistrano, Capifony and Deployer PHP.
* **Custom Deployer**, it's a strategy that assumes nothing about how you want
  to deploy your application. It's similar to Python's Fabric tool, so it's just
  an SSH toolkit instead of a deployer.

There are plans to add more deployment strategies in the future. Open issues to
ask for new strategies or vote for the existing issues so we can make better
decisions about what to implement next.

Configuration Files
-------------------

EasyDeploy uses plain PHP files to configure the deployment process. In other
words, you don't have to learn any special syntax and you won't face any of the
problems and constraints imposed by XML, JSON and YAML files.

The first time you run the `deploy` command in a Symfony application, an initial
config file is created for you. Go ahead, run `deploy` and open the generated
config file. This is what you'll see:

```php
// app/config/deploy_prod.php
use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class extends DefaultDeployer
{
    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('user@hostname')
            ->deployDir('/var/www/symfony-demo')
            ->repositoryUrl('git@github.com:symfony/symfony-demo.git')
            ->repositoryBranch('master')
        ;
    }
};
```

The configuration file must return a PHP class extending the base class
that corresponds to the deployment strategy used by your application. In PHP 7
this is easy because you can create anonymous classes (`new class extends ...`).

Then, configure the deployment process using the config builder object given to
you in the `configure()` method. Each config builder is unique for the
deployment strategy, so your IDE will only autocomplete the options available.

That's the best part of using a PHP config file. You don't have to read any
docs, you don't have to learn any syntax, you don't have to memorize any option,
you don't have to check deprecated/new options. All the available and updated
config options are given to you by the IDE autocompletion. Simple, smart, and
convenient.

Common Configuration Options
----------------------------

Most config options depend on the strategy used, but there are some options
common to all of them.

### SSH Agent Forwarding

[SSH agent forwarding][1] allows remote servers to use your local SSH keys. This
lets remote servers *fool* other services and make them believe that is your
local machine which is accessing those services.

This option is enabled by default, but [some people][2] consider it harmful, so
you can disable it as follows:

```php
public function configure()
{
    return $this->getConfigBuilder()
        ->useSshAgentForwarding(false)
        // ...
    ;
}
```

### Server Configuration

**This is the most important option** and it defines the SSH connection
credentials for all the servers involved in the deployment process. For simple
applications where you only have one server, you'll define something like this:

```php
public function configure()
{
    return $this->getConfigBuilder()
        ->server('user@hostname')
        // ...
    ;
}
```

The value of the `server()` option can be any string used to connect to the
server via SSH (anything that you may type in the `ssh ...` console command):

```php
// hostname (IP) and no user ('root' will be used)
->server('123.123.123.123')

// user + hostname
->server('user@example.com')

// user + host name + custom SSH port (default port: 22)
->server('user@example.com:22123')

// no user or hostname/IP (credentials will be read from ~/.ssh/config file)
->server('production')
```

> **TIP**
>
> Adding the usernames, hostnames, IPs and port numbers of the servers is boring
> and error prone. It's better to define that config in your local SSH config file.
> [Read this tutorial][4] to learn how to do that.

#### Multiple Servers

If your application is deployed to several servers, add the `server()` option
for each of those servers:

```php
public function configure()
{
    return $this->getConfigBuilder()
        ->server('deployer@hostname1')
        ->server('deployer@hostname2')
        // ...
    ;
}
```

#### Server Roles

By default, all configured servers are treated as the server where the Symfony
app is deployed. However, for complex apps you may have servers with different
responsibilities (workers, database servers, etc.).

These responsibilities are called **roles**. There is one reserved role called
**app** which is applied by default to all servers. You can define as many
custom roles as needed passing an array with the role names as the second
argument of the `server()` option:

```php
public function configure()
{
    return $this->getConfigBuilder()
        ->server('deployer@hostname1') // this server uses the default 'app' role
        ->server('deployer@hostname2', ['workers', 'worker-1'])
        ->server('deployer@hostname3', ['workers', 'worker-2'])
        ->server('deployer@hostname4', ['database'])
        // ...
    ;
}
```

Later, these role names will let you run some deployment commands on specific
servers. When using custom roles, don't forget to add the `app` role to those
servers where the Symfony applications is deployed. For example, if you use the
[blue/green deployment strategy][3], add the `app` role in addition to the
`blue` and `green` ones:

```php
public function configure()
{
    return $this->getConfigBuilder()
        ->server('deployer@hostname1', ['app', 'blue'])
        ->server('deployer@hostname2', ['app', 'green'])
        // ...
    ;
}
```

#### Server Properties

These properties are custom configuration options defined for a particular
server. You can define them as an associative array passed as the third argument
of the `server()` option. Later you'll see how to use these properties inside
the commands executed on any server:

```php
public function configure()
{
    return $this->getConfigBuilder()
        ->server('deployer@hostname1', ['app'], ['token' => '...'])
        ->server('deployer@hostname2', ['database'], ['use-lock' => false])
        // ...
    ;
}
```

Common Hooks
------------

The commands executed during a deployment and their order depends on the
deployer used. However, all deployers include *hooks* that let you execute your
own commands before, after or in the middle of that deployment flow. Technically
these hooks are methods of the PHP class used to define the deployment.

Each deployer defines its own hooks, but all of them define these common hooks:

```php
use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class extends DefaultDeployer
{
    public function configure()
    {
        // ...
    }


    public function beforeStartingDeploy()
    {
        // Deployment hasn't started yet, so here you can execute commands
        // to prepare the application or the remote servers
    }

    public function beforeFinishingDeploy()
    {
        // Deployment has finished but the deployer hasn't finished its
        // execution yet. Here you can run some checks in the deployed app
        // or send notifications.
    }

    public function beforeCancelingDeploy()
    {
        // An error happened during the deployment and remote servers are
        // going to be reverted to their original state. Here you can perform
        // clean ups or send notifications about the error.
    }


    public function beforeStartingRollback()
    {
        // Rollback hasn't started yet, so here you can execute commands
        // to prepare the application or the remote servers.
    }

    public function beforeCancelingRollback()
    {
        // An error happened during the rollback and remote servers are
        // going to be reverted to their original state. Here you can perform
        // clean ups or send notifications about the error.
    }

    public function beforeFinishingRollback()
    {
        // Rollback has finished but the deployer hasn't finished its
        // execution yet. Here you can run some checks in the reverted app
        // or send notifications.
    }
};
```

Common Methods
--------------

In addition to the common config options and hooks, every *deployer* has access
to some common methods that are useful to deploy and roll back the application.

### `runLocal()` Method

Executes the given shell command on the local computer. The working directory of
the command is set to the local project root directory, so you don't have to add
a `cd` command before the command:

```php
public function beforeStartingDeploy()
{
    $this->runLocal('./vendor/bin/simple-phpunit');
    // equivalent to the following:
    // $this->runLocal('cd /path/to/project && ./vendor/bin/simple-phpunit');
}
```

If the deployer allows to configure the Symfony environment, it is automatically
defined as an env var before executing the command:

```php
public function beforeStartingDeploy()
{
    $this->runLocal('./bin/console app:optimize-for-deploy');
    // equivalent to the following:
    // $this->runLocal('SYMFONY_ENV=prod ./bin/console app:optimize-for-deploy');
}
```

If you need to change the Symfony environment for some command, add the `--env`
option to the command, because it has preference over the env vars:

```php
public function beforeStartingDeploy()
{
    $this->runLocal('./bin/console app:optimize-for-deploy --env=dev');
    // equivalent to the following (--env=dev wins over SYMFONY_ENV=prod):
    // $this->runLocal('SYMFONY_ENV=prod ./bin/console app:optimize-for-deploy --env=dev');
}
```

The `runLocal()` method returns an immutable object of type `TaskCompleted`
which contains the command exit code, the full command output and other
utilities:

```php
public function beforeStartingDeploy()
{
    $result = $this->runLocal('./bin/console app:optimize-for-deploy');
    if (!$result->isSuccessful()) {
        $this->notify($result->getOutput());
    }
}
```

### `runRemote()` Method

Executes the given shell command on one or more remote servers. By default,
remote commands are executed only on the servers with the role `app`. Pass an
array of role names to execute the command on the servers that contain any of
those roles:

```php
public function beforeFinishingDeploy()
{
    $this->runRemote('./bin/console app:generate-xml-sitemap');
    $this->runRemote('/user/deployer/backup.sh', ['database']);
    $this->runRemote('/user/deployer/scripts/check.sh', ['app', 'workers']);
}
```

The working directory of the command is set to the remote project root
directory, so you don't have to add a `cd` command to that directory:

```php
public function beforeFinishingDeploy()
{
    $this->runRemote('./bin/console app:generate-xml-sitemap');
    // equivalent to the following:
    // $this->runRemote('cd /path/to/project && ./bin/console app:generate-xml-sitemap');
}
```

If the deployer allows to configure the Symfony environment, it is automatically
defined as an env var before executing the command:

```php
public function beforeFinishingDeploy()
{
    $this->runRemote('./bin/console app:generate-xml-sitemap');
    // equivalent to the following:
    // $this->runRemote('SYMFONY_ENV=prod ./bin/console app:generate-xml-sitemap');
}
```

If you need to change the Symfony environment for some command, add the `--env`
option to the command, because it has preference over the env vars:

```php
public function beforeFinishingDeploy()
{
    $this->runRemote('./bin/console app:generate-xml-sitemap --env=dev');
    // equivalent to the following (--env=dev wins over SYMFONY_ENV=prod):
    // $this->runRemote('SYMFONY_ENV=prod ./bin/console app:generate-xml-sitemap --env=dev');
}
```

The `runRemote()` method returns an array of immutable objects of type
`TaskCompleted` which contains the exit code, the full command output and other
utilities for the execution of the command on each server:

```php
public function beforeFinishingDeploy()
{
    $results = $this->runRemote('./bin/console app:generate-xml-sitemap');
    foreach ($results as $result) {
        $this->notify(sprintf('%d sitemaps on %s server', $result->getOutput(), $result->getServer()));
    }
}
```

### `log()` Method

This method appends the given message to the log file generated for each
deployment. If the deployment/rollback is run with the `-v` option, these
messages are displayed on the screen too:

```php
public function beforeFinishingDeploy()
{
    $this->log('Generating the Google XML Sitemap');
    $this->runRemote('./bin/console app:generate-xml-sitemap');
}
```

Command Properties
------------------

If you defined custom properties when configuring a server, you can use those
inside a shell command with the `{{ property-name }}` syntax. For example, if
you defined these servers:

```php
public function configure()
{
    return $this->getConfigBuilder()
        ->server('deployer@hostname1', ['app'], ['token' => '...'])
        ->server('deployer@hostname2', ['database'], ['use-lock' => false])
        // ...
    ;
}
```

Those properties can be part of any command run on those servers:

```php
public function beforeFinishingDeploy()
{
    $this->runRemote('./bin/console app:generate-xml-sitemap --token={{ token }}');
    $this->runRemote('/user/deployer/backup.sh --lock-tables={{ use-lock }}', ['database']);
}
```

[1]: https://developer.github.com/guides/using-ssh-agent-forwarding/
[2]: https://heipei.github.io/2015/02/26/SSH-Agent-Forwarding-considered-harmful/
[3]: https://martinfowler.com/bliki/BlueGreenDeployment.html
[4]: tutorials/local-ssh-config.md
