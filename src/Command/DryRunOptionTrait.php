<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\SymfonyConsole\TypedInput\TypedInput;

trait DryRunOptionTrait
{
    private $dryRunOptionName = 'dry-run';

    protected function addDryRunOption()
    {
        $this->addOption(
            $this->dryRunOptionName,
            null,
            InputOption::VALUE_NONE,
            'Run through the process without writing any data'
        );
    }

    protected function isDryRun(InputInterface $input): bool
    {
        return (new TypedInput($input))->getOption($this->dryRunOptionName);
    }

    protected function outputIsDryRunNotification(OutputInterface $output)
    {
        $output->writeln([
            '<comment>This is a DRY RUN, no data will be written</comment>',
            '',
        ]);
    }
}
