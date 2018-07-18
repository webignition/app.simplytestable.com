<?php
namespace AppBundle\Command\Maintenance;

use AppBundle\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractApplicationStateChangeCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_FAILURE = 1;

    const STATE_ACTIVE = 'active';
    const STATE_MAINTENANCE_READ_ONLY = 'maintenance-read-only';
    const STATE_MAINTENANCE_BACKUP_READ_ONLY = 'maintenance-backup-read-only';

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param string|null $name
     */
    public function __construct(ApplicationStateService $applicationStateService, $name = null)
    {
        parent::__construct($name);
        $this->applicationStateService = $applicationStateService;
    }

    /**
     * @param OutputInterface $output
     * @param string $state
     *
     * @return bool
     */
    protected function setState(OutputInterface $output, $state)
    {
        if ($this->applicationStateService->setState($state)) {
            $output->writeln('Set application state to "'.$state.'"');

            return true;
        }

        $output->writeln('Failed to set application state to "'.$state.'"');

        return false;
    }
}
