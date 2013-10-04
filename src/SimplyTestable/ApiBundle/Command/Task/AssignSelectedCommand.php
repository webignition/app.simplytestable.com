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

class AssignSelectedCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_FAILED_NO_WORKERS = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:assign-selected')
            ->setDescription('Assign to workers tasks selected for assignment')
            ->setHelp(<<<EOF
Assign to workers all tasks selected for assignment
EOF
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {        
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }   
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByState($this->getTaskService()->getQueuedForAssignmentState());
        $output->writeln(count($taskIds).' tasks queued for assignment');
        if (count($taskIds) === 0) {
            return self::RETURN_CODE_OK;
        }        
        
        $output->writeln('Attempting to assign tasks '.  implode(',', $taskIds));
        
        $tasks = $this->getTaskService()->getEntityRepository()->getCollectionById($taskIds);                
        $workers = $this->getWorkerService()->getActiveCollection();
                
        if (count($workers) === 0) {            
            $this->getLogger()->err("TaskAssignSelectedCommand::execute: Cannot assign, no workers.");
            return self::RETURN_CODE_FAILED_NO_WORKERS;
        }             
        
        $response = $this->getWorkerTaskAssignmentService()->assignCollection($tasks, $workers);        
        if ($response === 0) {
            $output->writeln('ok');
            $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();             
            
            $startedTasks = array();
            foreach ($tasks as $task) {
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
                
                $startedTasks = array_merge($startedTasks, $equivalentTasks, array($task));
            }
            
            foreach ($startedTasks as $startedTask) {
                $job = $startedTask->getJob();
                
                if ($job->getState()->getName() == 'job-queued') {                
                    $job->setState($this->getJobService()->getInProgressState());
                    $entityManager->persist($job);
                    $entityManager->flush();          
                }                  
            }
                        
        } else {
            $output->writeln('Failed to assign task collection, response '.$response);
        }
        
        return $response;
    }    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobService
     */
    private function getJobService() {
        return $this->getContainer()->get('simplytestable.services.jobservice');
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
     * @return \SimplyTestable\ApiBundle\Services\TaskPreProcessorFactoryService
     */    
    private function getTaskPreprocessorFactoryService() {
        return $this->getContainer()->get('simplytestable.services.TaskPreProcessorServiceFactory');
    }      
}