Using EasyDeploy when the server doesn't have PHP 7.1 installed
===============================================================

EasyDeploy code requires having PHP 7.1 installed to run it. However, as the
bundle should only be installed and executed on your local machine, you can use
it to deploy applications to servers that don't have PHP 7.1 installed.

Imagine that your production server still runs PHP 5.6 while your development
machine has already been updated to PHP 7.1. First, add the following to the
project's `composer.json` to make sure that all the installed code is compatible
with PHP 5.6:

```json
{
    "...": "...",
    "config": {
        "platform": {
            "php": "5.6.0"
        }
    }
}
```

Now, install or update the project dependencies so `composer.lock` only contains
PHP 5.6 packages. Now, install EasyDeploy only in the `dev` environment and add
the `--ignore-platform-reqs` option to tell Composer to ignore that the bundle
requires PHP 7.1 but the project requires all packages to be PHP 5.6 compatible:

```bash
$ composer require --dev --ignore-platform-reqs easycorp/easy-deploy-bundle
```

Then, make sure that Symfony's kernel loads EasyDeploy only in the `dev`
environment:

```
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        // ...

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            // ...
            $bundles[] = new EasyCorp\Bundle\EasyDeployBundle\EasyDeployBundle();
        }

        return $bundles;
    }

    // ...
}
```

Finally, check that when the application is deployed, Composer doesn't install
the packages of the `dev` environment. This requires adding the `--no-dev`
option to the `composer install` command and EasyDeploy does that automatically.
