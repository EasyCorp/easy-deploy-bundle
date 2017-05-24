Installation
============

EasyDeploy is distributed as a bundle that must be installed in each Symfony
application that you want to deploy.

If you use Symfony Flex
-----------------------

```console
$ cd your-symfony-project/
$ composer require --dev easycorp/easy-deploy-bundle
```

And that's it! You can skip the rest of this article.

If you don't use Symfony Flex
-----------------------------

**Step 1.** Download the bundle:

```console
$ cd your-symfony-project/
$ composer require --dev easycorp/easy-deploy-bundle
```

**Step 2.** Enable the bundle:

```php
// ...
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
