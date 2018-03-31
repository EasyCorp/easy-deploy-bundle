<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle;

use EasyCorp\Bundle\EasyDeployBundle\Helper\Str;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Filesystem\Filesystem;

final class Logger
{
    private $isDebug;
    private $output;
    private $logFilePath;

    public function __construct(Context $context)
    {
        $this->isDebug = $context->isDebug();

        $this->output = $context->getOutput();
        $this->output->setFormatter($this->createOutputFormatter());

        $this->logFilePath = $context->getLogFilePath();
        $this->initializeLogFile();
    }

    public function log(string $message): void
    {
        $isPriorityMessage = Str::startsWith($message, '<h1>');
        $isResultMessage = Str::contains($message, '<error>') || Str::contains($message, '<success>');
        if ($this->isDebug || $isPriorityMessage || $isResultMessage) {
            $this->output->writeln($message);
        }

        $this->writeToLogFile($message);
    }

    private function createOutputFormatter(): OutputFormatter
    {
        return new OutputFormatter(true, [
            'command' => new OutputFormatterStyle('yellow', null),
            'error' => new OutputFormatterStyle('red', null, ['bold', 'reverse']),
            'hook' => new OutputFormatterStyle('blue', null, ['bold']),
            'server' => new OutputFormatterStyle('magenta', null),
            'stream' => new OutputFormatterStyle(null, null),
            'success' => new OutputFormatterStyle('green', null, ['bold', 'reverse']),
            'ok' => new OutputFormatterStyle('green', null),
            'warn' => new OutputFormatterStyle('yellow', null),
            'h1' => new OutputFormatterStyle('blue', null, ['bold']),
            'h2' => new OutputFormatterStyle(null, null, []),
            'h3' => new OutputFormatterStyle(null, null, []),
        ]);
    }

    private function initializeLogFile(): void
    {
        (new Filesystem())->dumpFile($this->logFilePath, '');
        $this->writeToLogFile(sprintf("%s\nDeployment started at %s\n%s", Str::lineSeparator('='), date('r'), Str::lineSeparator('=')));
    }

    private function writeToLogFile(string $message): void
    {
        $loggedMessage = $this->processLogMessageForFile($message);
        file_put_contents($this->logFilePath, $loggedMessage.PHP_EOL, FILE_APPEND);
    }

    private function processLogMessageForFile(string $message): string
    {
        $replacements = [
            '/<command>(.*)<\/>/' => '"$1"',
            '/<server>(.*)<\/>/' => '$1',
            '/<stream>(.*)<\/>/' => '$1',
            '/<hook>(.*)<\/>/' => '__ $1 __',
            '/<ok>(.*)<\/>/' => '$1',
            '/<warn>(.*)<\/>/' => '$1',
            '/<h1>(.*)<\/>/' => "\n===> $1",
            '/<h2>(.*)<\/>/' => '---> $1',
            '/<h3>(.*)<\/>/' => '$1',
            '/<success>(.*)<\/>/' => sprintf("\n%s\n$1\n%s\n", Str::lineSeparator(), Str::lineSeparator()),
            '/<error>(.*)<\/>/' => sprintf("\n%s\n$1\n%s\n", Str::lineSeparator('*'), Str::lineSeparator('*')),
        ];

        return preg_replace(array_keys($replacements), array_values($replacements), $message);
    }
}
