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

class RollbackCommand extends Command
{
    private $projectDir;
    private $logDir;
    private $configFilePath;

    public function __construct(string $projectDir, string $logDir)
    {
        $this->projectDir = $projectDir;
        $this->logDir = $logDir;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('rollback')
            ->setDescription('Deploys a Symfony application to one or more remote servers.')
            ->setHelp('...')
            ->addArgument('stage', InputArgument::OPTIONAL, 'The stage to roll back ("production", "staging", etc.)', 'prod')
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Load configuration from the given file path')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Shows the commands to perform the roll back without actually executing them')
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

        throw new \RuntimeException(sprintf("The default configuration file does not exist or it's not readable, and no custom configuration file was given either. Create the '%s' configuration file and run this command again.", $defaultConfigPath));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logFilePath = sprintf('%s/deploy_%s.log', $this->logDir, $input->getArgument('stage'));
        $context = new Context($input, $output, $this->projectDir, $logFilePath, true === $input->getOption('dry-run'), $output->isVerbose());

        $deployer = include $this->configFilePath;
        $deployer->initialize($context);
        $deployer->doRollback();

        return 0;
    }
}
