<?php
namespace SimplyTestable\ApiBundle\Command\Task;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;

class EnqueueCancellationForAwaitingCancellationCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:enqueue-cancellation-for-awaiting-cancellation')
            ->setDescription('Enqueue resque jobs for cancelling tasks that are awaiting cancellation')
            ->setHelp(<<<EOF
Enqueue resque jobs for cancelling tasks that are awaiting cancellation
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {   
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }         
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByState($this->getTaskService()->getAwaitingCancellationState());        
        $output->writeln(count($taskIds).' tasks to enqueue for cancellation');
        if (count($taskIds) === 0) {
            return self::RETURN_CODE_OK;
        } 
        
        $output->writeln('Enqueuing for cancellation tasks '.  implode(',', $taskIds));        
        $this->getResqueQueueService()->add(
            'SimplyTestable\ApiBundle\Resque\Job\TaskCancelCollectionJob',
            'task-cancel',
            array(
                'ids' => implode(',', $taskIds)
            )              
        );        
        
        return self::RETURN_CODE_OK;
    }    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\TaskService
     */
    private function getTaskService() {
        return $this->getContainer()->get('simplytestable.services.taskservice');
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