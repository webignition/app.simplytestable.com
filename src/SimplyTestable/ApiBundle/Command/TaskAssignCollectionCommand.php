<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class TaskAssignCollectionCommand extends ContainerAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:assigncollection')
            ->setDescription('Assign a collection of tasks to workers')
            ->addArgument('ids', InputArgument::REQUIRED, 'ids of tasks to assign')
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
            ->setHelp(<<<EOF
Assign a collection of tasks to workers
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
        
        $taskIds = explode(',', $input->getArgument('ids'));        
        $tasks = $this->getTaskService()->getEntityRepository()->getCollectionById($taskIds);
        
        $response = $this->getWorkerTaskAssignmentService()->assignCollection($tasks);
        
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
     * @return \SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService
     */    
    private function getWorkerTaskAssignmentService() {
        return $this->getContainer()->get('simplytestable.services.workertaskassignmentservice');
    }
}