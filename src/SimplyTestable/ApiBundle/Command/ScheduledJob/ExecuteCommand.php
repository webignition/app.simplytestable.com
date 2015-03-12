<?php
namespace SimplyTestable\ApiBundle\Command\ScheduledJob;

use SimplyTestable\ApiBundle\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:scheduledjob:execute')
            ->setDescription('Start a new job from a scheduled job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of scheduled job to execute')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            if (!$this->getResqueQueueService()->contains('scheduled-job', ['id' => (int)$input->getArgument('id')])) {
                $this->getResqueQueueService()->enqueue(
                    $this->getResqueJobFactoryService()->create(
                        'scheduledjob-execute',
                        ['id' => (int)$input->getArgument('id')]
                    )
                );
            }

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        return self::RETURN_CODE_OK;
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\QueueService
     */
    private function getResqueQueueService() {
        return $this->getContainer()->get('simplytestable.services.resque.queueService');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\JobFactoryService
     */
    private function getResqueJobFactoryService() {
        return $this->getContainer()->get('simplytestable.services.resque.jobFactoryService');
    }
}