<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class TaskCancelCollectionCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:cancelcollection')
            ->setDescription('Cancel a collection of tasks')
            ->addArgument('ids', InputArgument::REQUIRED, 'comma-separated list of ids of tasks to cancel') 
            ->setHelp(<<<EOF
Cancel a collection of tasks
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {    
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }
        
        $this->getContainer()->get('logger')->info('TaskCancelCollectionCommand::execute: raw ids ['.$input->getArgument('ids').']');
        
        $taskIds = explode(',', $input->getArgument('ids'));
        
        $taskIdsByWorker = array();        
        foreach ($taskIds as $taskId) {
            $task = $this->getTaskService()->getById($taskId);
            
            if ($task->hasWorker()) {
                if (!isset($taskIdsByWorker[$task->getWorker()->getHostname()])) {
                    $taskIdsByWorker[$task->getWorker()->getHostname()] = array();
                }

                $taskIdsByWorker[$task->getWorker()->getHostname()][] = $task;                
            } else {
                $this->getTaskService()->cancel($task);
            }
        }
        
        foreach ($taskIdsByWorker as $tasks) {
            $this->getWorkerTaskCancellationService()->cancelCollection($tasks);
        }        
    }
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\TaskService
     */
    private function getTaskService() {
        return $this->getContainer()->get('simplytestable.services.taskservice');
    }  
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\WorkerTaskCancellationService
     */    
    private function getWorkerTaskCancellationService() {
        return $this->getContainer()->get('simplytestable.services.workertaskcancellationservice');
    }
}