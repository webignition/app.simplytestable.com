<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class TaskCancelCollectionCommand extends ContainerAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:cancelcollection')
            ->setDescription('Cancel a collection of tasks')
            ->addArgument('ids', InputArgument::REQUIRED, 'comma-separated list of ids of tasks to cancel')
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
            ->setHelp(<<<EOF
Cancel a collection of tasks
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
        
        $this->getContainer()->get('logger')->info('TaskCancelCollectionCommand::execute: raw ids ['.$input->getArgument('ids').']');
        
        $taskIds = explode(',', $input->getArgument('ids'));
        
        $taskIdsByWorker = array();        
        foreach ($taskIds as $taskId) {
            $this->getContainer()->get('logger')->info('TaskCancelCollectionCommand::execute: taskId ['.$taskId.']');
            $task = $this->getTaskService()->getById($taskId);
            
            if ($task->hasWorker()) {
                if (!isset($tasksByWorker[$task->getWorker()->getHostname()])) {
                    $tasksByWorker[$task->getWorker()->getHostname()] = array();
                }

                $tasksByWorker[$task->getWorker()->getHostname()][] = $task;                
            } else {
                $this->getTaskService()->cancel($task);
            }
        }
        
        foreach ($taskIdsByWorker as $tasks) {
            $this->getWorkerTaskCancellationService()->cancelCollection($tasks);
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
     * @return SimplyTestable\ApiBundle\Services\WorkerTaskCancellationService
     */    
    private function getWorkerTaskCancellationService() {
        return $this->getContainer()->get('simplytestable.services.workertaskcancellationservice');
    }
}