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

use EasyCorp\Bundle\EasyDeployBundle\Helper\Str;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * It implements the "Adapter" pattern to allow working with the configuration
 * in a consistent manner, even if the configuration of each deployer is
 * completely different and defined using incompatible objects.
 */
final class ConfigurationAdapter
{
    private $config;
    /** @var ParameterBag */
    private $options;

    public function __construct(AbstractConfiguration $config)
    {
        $this->config = $config;
    }

    public function __toString(): string
    {
        return Str::formatAsTable($this->getOptions()->all());
    }

    public function get(string $optionName)
    {
        if (!$this->getOptions()->has($optionName)) {
            throw new \InvalidArgumentException(sprintf('The "%s" option is not defined.', $optionName));
        }

        return $this->getOptions()->get($optionName);
    }

    private function getOptions(): ParameterBag
    {
        if (null !== $this->options) {
            return $this->options;
        }

        // it's not the most beautiful code possible, but making the properties
        // private and the methods public allows to configure the deployment using
        // a config builder and the IDE autocompletion. Here we need to access
        // those private properties and their values
        $options = new ParameterBag();
        $r = new \ReflectionObject($this->config);
        foreach ($r->getProperties() as $property) {
            try {
                $property->setAccessible(true);
                $options->set($property->getName(), $property->getValue($this->config));
            } catch (\ReflectionException $e) {
                // ignore this error
            }
        }

        return $this->options = $options;
    }
}
