EasyDeployBundle
================

**EasyDeployBundle is the easiest way to deploy your Symfony applications.**

### Features

  * Zero dependencies. No Python. No Ruby. No Capistrano. No Ansible. Nothing.
  * Zero configuration files. No YAML. No XML. No JSON. Just pure PHP awesomeness.
  * Multi-server and multi-stage deployment (e.g. "production", "staging", "qa").
  * Zero downtime deployments.
  * Supports Symfony 2.7+, Symfony 3.x and Symfony 4.x applications.
  * Compatible with GitHub, BitBucket, GitLab and your own Git servers.

### Requirements

  * Your local machine: PHP 7.1 or higher and a SSH client.
  * Your remote servers: they allow SSH connections from the local machine.
  * Your application: it can use any version of Symfony (2.7+, 3.x, 4.x).

### Documentation

* [Installation](doc/installation.md)
* [Getting Started](doc/getting-started.md)
* [Configuration](doc/configuration.md)
* [Default Deployer](doc/default-deployer.md)
* [Custom Deployer](doc/custom-deployer.md)

#### Tutorials

* [Creating a Local SSH Configuration File](doc/tutorials/local-ssh-config.md)
* [Troubleshooting Connection Issues to Remote SSH Servers](doc/tutorials/remote-ssh-config.md)
* [Cloning the Application Code on Remote Servers](doc/tutorials/remote-code-cloning.md)

> **NOTE**
> EasyDeploy does not "provision" servers (like installing a web server and the
> right PHP version for your application); use Ansible if you need that.
> EasyDeploy does not deploy containerized applications: use Kubernetes if you
> need that.
