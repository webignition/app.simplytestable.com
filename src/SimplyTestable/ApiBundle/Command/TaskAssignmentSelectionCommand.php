<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TaskAssignmentSelectionCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;
    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:assign:select')
            ->setDescription('Select the oldtest queued tasks from each job with queued tasks and queue them for assignment to workers')
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
            ->setHelp(<<<EOF
Select the oldtest queued tasks from each job with queued tasks and queue them for assignment to workers
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {   
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }          
        
        if ($input->hasArgument('http-fixture-path')) {
            $httpClient = $this->getContainer()->get('simplytestable.services.httpClient');
            
            if ($httpClient instanceof \webignition\Http\Mock\Client\Client) {
                $httpClient->getStoredResponseList()->setFixturesPath($input->getArgument('http-fixture-path'));
            }            
        }
      
        if ($this->getTaskService()->getQueuedCount() === 0) {
            return self::RETURN_CODE_OK;
        }
        
        $workerCount = $this->getWorkerService()->count();
        
        $tasks = $this->getTaskAssignmentSelectionService()->selectTasks($workerCount);
        $taskGroups = $this->getTaskGroups($tasks, $workerCount);
        
        $this->getContainer()->get('logger')->info('TaskAssignmentSelectionCommand:execute: tasks found ['.count($tasks).']');
        
        foreach ($taskGroups as $taskGroup) {
            $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();            
            $taskIds = array();
            
            foreach ($taskGroup as $task) {
                $this->getContainer()->get('logger')->info('TaskAssignmentSelectionCommand:execute: selected task id ['.$task->getId().']');

                $task->setState($this->getTaskService()->getQueuedForAssignmentState());
                $entityManager->persist($task);
                
                $taskIds[] = $task->getId();             
            }
            
            $this->getResqueQueueService()->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignCollectionJob',
                'task-assign-collection',
                array(
                    'ids' => implode(',', $taskIds)
                )
            );            

            $entityManager->flush();            
        }
        
        if ($this->getTaskService()->hasQueuedTasks()) {
            if ($this->getResqueQueueService()->isEmpty('task-assignment-selection')) {                
                $this->getResqueQueueService()->add(
                    'SimplyTestable\ApiBundle\Resque\Job\TaskAssignmentSelectionJob',
                    'task-assignment-selection'
                );             
            }              
        }      
    }

    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\TaskAssignmentSelectionService
     */    
    private function getTaskAssignmentSelectionService() {
        return $this->getContainer()->get('simplytestable.services.taskassignmentselectionservice');
    }
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\ResqueQueueService
     */        
    private function getResqueQueueService() {
        return $this->getContainer()->get('simplytestable.services.resqueQueueService');
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
     * @return \SimplyTestable\ApiBundle\Services\WorkerService
     */        
    private function getWorkerService() {
        return $this->getContainer()->get('simplytestable.services.workerservice');
    } 
    
    
    /**
     * 
     * @param array $tasks
     * @param int $groupCount
     * @return array
     */
    private function getTaskGroups($tasks, $groupCount) {
        $groupedTasks = array();
        $groupIndex = 0;
        $maximumGroupIndex = $groupCount - 1;
        
        foreach ($tasks as $task) {
            if (!isset($groupedTasks[$groupIndex])) {
                $groupedTasks[$groupIndex] = array();
            }
            
            $groupedTasks[$groupIndex][] = $task;
            
            $groupIndex++;
            if ($groupIndex > $maximumGroupIndex) {
                $groupIndex = 0;
            }
        }
        
        return $groupedTasks;
    }    
}