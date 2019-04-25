<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

    protected function isDryRun(InputInterface $input)
    {
        return filter_var($input->getOption($this->dryRunOptionName), FILTER_VALIDATE_BOOLEAN);
    }

    protected function outputIsDryRunNotification(OutputInterface $output)
    {
        $output->writeln([
            '<comment>This is a DRY RUN, no data will be written</comment>',
            '',
        ]);
    }
}
