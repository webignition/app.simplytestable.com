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
       
        if ($this->getWorkerTaskAssignmentService()->assign($task) === false) {
            throw new \LogicException('Task assignment to worker failed, check log for details');
        }
        
        if ($task->getJob()->getState()->getName() == 'job-queued') {
            $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
            $task->getJob()->setNextState();
            
            $entityManager->persist($task->getJob());
            $entityManager->flush();               
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
     * @return SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService
     */    
    private function getWorkerTaskAssignmentService() {
        return $this->getContainer()->get('simplytestable.services.workertaskassignmentservice');
    }
}