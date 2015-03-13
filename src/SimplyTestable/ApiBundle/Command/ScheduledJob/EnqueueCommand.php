<?php
namespace SimplyTestable\ApiBundle\Command\ScheduledJob;

use SimplyTestable\ApiBundle\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactoryService as ResqueJobFactoryService;

class EnqueueCommand extends BaseCommand {
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:scheduledjob:enqueue')
            ->setDescription('Start a new job from a scheduled job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of scheduled job to execute')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getLogger()->notice('simplytestable:scheduledjob:enqueue [' . $input->getArgument('id') . '] start');

        if ($this->getResqueQueueService()->contains('scheduled-job', ['id' => (int)$input->getArgument('id')])) {
            $this->getLogger()->notice('simplytestable:scheduledjob:enqueue [' . $input->getArgument('id') . '] already in queue');
        } else {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'scheduledjob-execute',
                    ['id' => (int)$input->getArgument('id')]
                )
            );

            $this->getLogger()->notice('simplytestable:scheduledjob:enqueue [' . $input->getArgument('id') . '] enqueuing');
        }
    }


    /**
     *
     * @return ResqueQueueService
     */
    private function getResqueQueueService() {
        return $this->getContainer()->get('simplytestable.services.resque.queueService');
    }


    /**
     *
     * @return ResqueJobFactoryService
     */
    private function getResqueJobFactoryService() {
        return $this->getContainer()->get('simplytestable.services.resque.jobFactoryService');
    }

}