<?php
namespace SimplyTestable\ApiBundle\Command\Task\Assign;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class Command extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_FAILED_NO_WORKERS = 2;
    const RETURN_CODE_FAILED_TASK_INVALID = 4;    
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 5;    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:assign')
            ->setDescription('Assign a task to a worker')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to assign')
            ->setHelp(<<<EOF
Assign a task to a worker
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {     
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }
        
        $task = $this->getTaskService()->getById((int)$input->getArgument('id'));               
        if (is_null($task)) {
            return self::RETURN_CODE_FAILED_TASK_INVALID;
        }
        
        if ($this->getTaskPreprocessorFactoryService()->hasPreprocessor($task)) {            
            $preProcessorResponse = false;
            
            try {
                $preProcessorResponse = $this->getTaskPreprocessorFactoryService()->getPreprocessor($task)->process($task);
            } catch (\Exception $e) {
            }
            
            if ($preProcessorResponse === true) {
                return self::RETURN_CODE_OK;
            }
        }        
        
        $workers = $this->getWorkerService()->getActiveCollection();
        if (count($workers) === 0) {
            $this->getLogger()->error("TaskAssignCommand::execute: Cannot assign, no workers.");

            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'task-assign',
                    ['id' => $task->getId()]
                )
            );
            
            return self::RETURN_CODE_FAILED_NO_WORKERS;
        }
        
        $result = $this->getWorkerTaskAssignmentService()->assign($task, $workers);

        // 0,1,2,3
        if ($result === 0) {
            $equivalentTasks = $this->getTaskService()->getEquivalentTasks($task->getUrl(), $task->getType(), $task->getParametersHash(), array(
                $this->getTaskService()->getQueuedForAssignmentState(),
                $this->getTaskService()->getQueuedState()                
            ));
            
            foreach ($equivalentTasks as $equivalentTask) {                
                $this->getTaskService()->setStarted(
                    $equivalentTask,
                    $task->getWorker(),
                    $task->getRemoteId()
                );  
                
                $this->getTaskService()->persistAndFlush($equivalentTask);
            }
            
            $startedTasks = array_merge(array($task), $equivalentTasks);
            
            foreach ($startedTasks as $startedTask) {                
                if ($startedTask->getJob()->getState()->getName() == 'job-queued') {
                    $entityManager = $this->getContainer()->get('doctrine')->getManager();                    
                    $startedTask->getJob()->setNextState();                

                    $entityManager->persist($startedTask->getJob());
                    $entityManager->flush();               
                }                
            }
            
            return self::RETURN_CODE_OK;
        }
        
        // If could not be assgined to any workers
        if ($result === 3) {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'task-assign',
                    ['id' => $task->getId()]
                )
            );
        }
        
        return $result;
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
     * @return \SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService
     */    
    private function getWorkerTaskAssignmentService() {
        return $this->getContainer()->get('simplytestable.services.workertaskassignmentservice');
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
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\TaskPreProcessor\FactoryService
     */    
    private function getTaskPreprocessorFactoryService() {
        return $this->getContainer()->get('simplytestable.services.TaskPreProcessorServiceFactory');
    }     
}