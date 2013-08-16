<?php
namespace SimplyTestable\ApiBundle\Command\Task;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AssignmentSelectionCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;
    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:assign:select')
            ->setDescription('Select the oldtest queued tasks from each job with queued tasks and queue them for assignment to workers')
            ->setHelp(<<<EOF
Select the oldtest queued tasks from each job with queued tasks and queue them for assignment to workers
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {   
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }
        
        $queuedTaskCount = $this->getTaskService()->getQueuedCount();
        
        $output->writeln($queuedTaskCount.' total tasks queued for processing');
        if ($queuedTaskCount === 0) {
            $output->writeln('Stopping');
            return self::RETURN_CODE_OK;
        }
        
        $workerCount = $this->getWorkerService()->count();
        $output->writeln($workerCount.' workers in the processing pool');
        
        $tasks = $this->getTaskAssignmentSelectionService()->selectTasks($workerCount);
        $selectedTaskCount = count($tasks);
        
        $output->writeln($selectedTaskCount.' tasks selected');   
        if ($selectedTaskCount === 0) {
            return self::RETURN_CODE_OK;
        }
        
        $taskGroups = $this->getTaskGroups($tasks, $workerCount);
        
        $output->writeln(count($taskGroups).' task groups');
        
        $this->getContainer()->get('logger')->info('TaskAssignmentSelectionCommand:execute: tasks found ['.count($tasks).']');
        
        foreach ($taskGroups as $taskGroupIndex => $taskGroup) {
            $output->writeln('Processing task group '.($taskGroupIndex + 1));
            
            $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();            
            $taskIds = array();
            
            foreach ($taskGroup as $task) {
                $this->getContainer()->get('logger')->info('TaskAssignmentSelectionCommand:execute: selected task id ['.$task->getId().']');

                $task->setState($this->getTaskService()->getQueuedForAssignmentState());
                $entityManager->persist($task);
                
                $taskIds[] = $task->getId();             
            }
            
            $output->writeln('Enqueuing for assignment tasks: '.  implode(',', $taskIds));            
            
            $this->getResqueQueueService()->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignCollectionJob',
                'task-assign-collection',
                array(
                    'ids' => implode(',', $taskIds)
                )
            );            

            $entityManager->flush();            
        }
        
        if (!$this->getTaskService()->hasQueuedTasks()) {
            $output->writeln('No queued tasks');
        }
        
        $output->writeln('Stopping');
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