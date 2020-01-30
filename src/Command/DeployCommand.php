<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Command;

use EasyCorp\Bundle\EasyDeployBundle\Context;
use EasyCorp\Bundle\EasyDeployBundle\Helper\SymfonyConfigPathGuesser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;

class DeployCommand extends Command
{
    private $fileLocator;
    private $projectDir;
    private $logDir;
    private $configFilePath;

    public function __construct(FileLocator $fileLocator, string $projectDir, string $logDir)
    {
        $this->fileLocator = $fileLocator;
        $this->projectDir = realpath($projectDir);
        $this->logDir = $logDir;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('Deploys a Symfony application to one or more remote servers.')
            ->setHelp('...')
            ->addArgument('stage', InputArgument::OPTIONAL, 'The stage to deploy to ("production", "staging", etc.)', 'prod')
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Load configuration from the given file path')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Shows the commands to perform the deployment without actually executing them')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $customConfigPath = $input->getOption('configuration');
        if (null !== $customConfigPath && !is_readable($customConfigPath)) {
            throw new \RuntimeException(sprintf("The given configuration file ('%s') does not exist or it's not readable.", $customConfigPath));
        }

        if (null !== $customConfigPath && is_readable($customConfigPath)) {
            return $this->configFilePath = $customConfigPath;
        }

        $defaultConfigPath = SymfonyConfigPathGuesser::guess($this->projectDir, $input->getArgument('stage'));
        if (is_readable($defaultConfigPath)) {
            return $this->configFilePath = $defaultConfigPath;
        }

        $this->createDefaultConfigFile($input, $output, $defaultConfigPath, $input->getArgument('stage'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logFilePath = sprintf('%s/deploy_%s.log', $this->logDir, $input->getArgument('stage'));
        $context = new Context($input, $output, $this->projectDir, $logFilePath, true === $input->getOption('dry-run'), $output->isVerbose());

        $deployer = include $this->configFilePath;
        $deployer->initialize($context);
        $deployer->doDeploy();

        return 0;
    }

    private function createDefaultConfigFile(InputInterface $input, OutputInterface $output, string $defaultConfigPath, string $stageName): void
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(sprintf("\n<bg=yellow> WARNING </> There is no config file to deploy '%s' stage.\nDo you want to create a minimal config file for it? [Y/n] ", $stageName), true);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln(sprintf('<fg=green>OK</>, but before running this command again, create this config file: %s', $defaultConfigPath));
        } else {
            (new Filesystem())->copy($this->fileLocator->locate('@EasyDeployBundle/Resources/skeleton/deploy.php.dist'), $defaultConfigPath);
            $output->writeln(sprintf('<fg=green>OK</>, now edit the "%s" config file and run this command again.', $defaultConfigPath));
        }

        exit(0);
    }
}
