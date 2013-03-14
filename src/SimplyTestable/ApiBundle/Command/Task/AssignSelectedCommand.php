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
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
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
        
        if ($input->hasArgument('http-fixture-path')) {
            $httpClient = $this->getContainer()->get('simplytestable.services.httpClient');
            
            if ($httpClient instanceof \webignition\Http\Mock\Client\Client) {
                $httpClient->getStoredResponseList()->setFixturesPath($input->getArgument('http-fixture-path'));
            }            
        }   
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByState($this->getTaskService()->getQueuedForAssignmentState());
        if (count($taskIds) === 0) {
            return self::RETURN_CODE_OK;
        }        
        
        $tasks = $this->getTaskService()->getEntityRepository()->getCollectionById($taskIds);                
        $workers = $this->getWorkerService()->getActiveCollection();
        
        if (count($workers) === 0) {
            $this->getLogger()->err("TaskAssignSelectedCommand::execute: Cannot assign, no workers.");
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