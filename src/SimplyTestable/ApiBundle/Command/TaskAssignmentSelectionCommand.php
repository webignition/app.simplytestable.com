<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class TaskAssignmentSelectionCommand extends ContainerAwareCommand
{
    
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
        if ($input->hasArgument('http-fixture-path')) {
            $httpClient = $this->getContainer()->get('simplytestable.services.httpClient');
            
            if ($httpClient instanceof \webignition\Http\Mock\Client\Client) {
                $httpClient->getStoredResponseList()->setFixturesPath($input->getArgument('http-fixture-path'));
            }            
        }
        
        $taskAssignmentLimitPerJob = $this->getWorkerService()->count() * 2;
        
        $tasks = $this->getTaskAssignmentSelectionService()->selectTasks($taskAssignmentLimitPerJob);
        
        $this->getContainer()->get('logger')->info('TaskAssignmentSelectionCommand:execute: tasks found ['.count($tasks).']');
        
        if (count($tasks)) {
            $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();            

            foreach ($tasks as $task) {
                $this->getContainer()->get('logger')->info('TaskAssignmentSelectionCommand:execute: selected task id ['.$task->getId().']');

                $task->setState($this->getTaskService()->getQueuedForAssignmentState());
                $entityManager->persist($task);            

                $this->getContainer()->get('simplytestable.services.resqueQueueService')->add(
                    'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
                    'task-assign',
                    array(
                        'id' => $task->getId()
                    )
                );             
            }

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
}