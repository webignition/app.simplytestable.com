<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends BaseCommand
{
    const STATE_ACTIVE = 'active';
    const STATE_MAINTENANCE_READ_ONLY = 'maintenance-read-only';
    const STATE_MAINTENANCE_BACKUP_READ_ONLY = 'maintenance-backup-read-only';

    protected function setState(OutputInterface $output, $state)
    {
        if ($this->getApplicationStateService()->setState($state)) {
            $output->writeln('Set application state to "'.$state.'"');
            return 0;
        }

        $output->writeln('Failed to set application state to "'.$state.'"');
        return 1;
    }
}
