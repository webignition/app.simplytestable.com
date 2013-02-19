<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class TaskAssignCommand extends ContainerAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:assign')
            ->setDescription('Assign a task to a worker')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to assign')
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
            ->setHelp(<<<EOF
Assign a task to a worker
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
        
        $task = $this->getTaskService()->getById((int)$input->getArgument('id'));               
        if (is_null($task)) {
            return 4;
        }
        
        $workers = $this->getWorkerService()->getActiveCollection();
        if (count($workers) === 0) {
            $this->getLogger()->err("TaskAssignCommand::execute: Cannot assign, no workers.");                       
            $this->getResqueQueueService()->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
                'task-assign',
                array(
                    'id' => $task->getId()
                )
            ); 
            
            return 2;
        }         
        
        $result = $this->getWorkerTaskAssignmentService()->assign($task, $workers);

        // 0,1,2,3
        if ($result === 0) {
            if ($task->getJob()->getState()->getName() == 'job-queued') {
                $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
                $task->getJob()->setNextState();                
                
                $entityManager->persist($task->getJob());
                $entityManager->flush();               
            }            
        }
        
        // If could not be assgined to any workers
        if ($result === 3) {
            $this->getResqueQueueService()->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
                'task-assign',
                array(
                    'id' => $task->getId()
                )
            );           
        }
        
        return $result;
    }
    
    
    /**
     *
     * @return Logger
     */
    private function getLogger() {
        return $this->getContainer()->get('logger');
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
     * @return \SimplyTestable\ApiBundle\Services\WorkerService
     */    
    private function getWorkerService() {
        return $this->getContainer()->get('simplytestable.services.workerservice');
    }    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService
     */    
    private function getWorkerTaskAssignmentService() {
        return $this->getContainer()->get('simplytestable.services.workertaskassignmentservice');
    }    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\ResqueQueueService
     */    
    private function getResqueQueueService() {
        return $this->getContainer()->get('simplytestable.services.resquequeueservice');
    }
}