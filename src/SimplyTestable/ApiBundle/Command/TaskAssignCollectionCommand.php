<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;

class TaskAssignCollectionCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_FAILED_NO_WORKERS = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = -1;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:assigncollection')
            ->setDescription('Assign a collection of tasks to workers')
            ->addArgument('ids', InputArgument::REQUIRED, 'ids of tasks to assign')
            ->setHelp(<<<EOF
Assign a collection of tasks to workers
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }
        
        $taskIds = explode(',', $input->getArgument('ids'));              
        $tasks = $this->getTaskService()->getEntityRepository()->getCollectionById($taskIds);
        
        if (count($taskIds) === 0) {
            return self::RETURN_CODE_OK;
        }
        
        $workers = $this->getWorkerService()->getActiveCollection();        
        if (count($workers) === 0) {            
            $this->getLogger()->err("TaskAssignCollectionCommand::execute: Cannot assign, no workers.");                       
            $this->getContainer()->get('simplytestable.services.resqueQueueService')->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignCollectionJob',
                'task-assign-collection',
                array(
                    'ids' => implode(',', $taskIds)
                )
            );
            
            return self::RETURN_CODE_FAILED_NO_WORKERS;
        }
        
        $response = $this->getWorkerTaskAssignmentService()->assignCollection($tasks, $workers);        
        if ($response === 0) {
            $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();

            $job = $tasks[0]->getJob();
            if ($job->getState()->getName() == 'job-queued') {                
                $job->setState($this->getJobService()->getInProgressState());
                $entityManager->persist($job);          
            }       

            $entityManager->flush();            
        } else {
            $this->getContainer()->get('simplytestable.services.resqueQueueService')->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignCollectionJob',
                'task-assign-collection',
                array(
                    'ids' => implode(',', $taskIds)
                )
            );             
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
}