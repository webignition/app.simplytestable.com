<?php
namespace SimplyTestable\ApiBundle\Command\Worker;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Command\BaseCommand;

class TaskNotificationCommand extends BaseCommand {

    const RETURN_CODE_OK = 0;

    protected function configure()
    {
        $this
            ->setName('simplytestable:worker:tasknotification')
            ->setDescription('Notify all workers of tasks ready to be carried out')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->getWorkerTaskNotificationService()->notify();
        return self::RETURN_CODE_OK;
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Worker\TaskNotificationService
     */
    private function getWorkerTaskNotificationService() {
        return $this->getContainer()->get('simplytestable.services.worker.taskNotificationService');
    }
}