<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class TaskCancelCommand extends ContainerAwareCommand
{
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:task:cancel')
            ->setDescription('Cancel a task')
            ->addArgument('id', InputArgument::REQUIRED, 'id of task to cancel')
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
            ->setHelp(<<<EOF
Cancel a task
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
       
        if ($this->getWorkerTaskCancellationService()->cancel($task) === false) {
            throw new \LogicException('Task cancellation failed, check log for details');
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