<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class TaskCancelCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_TASK_DOES_NOT_EXIST = -1;
    const RETURN_CODE_FAILED_DUE_TO_WRONG_STATE = -2;
    
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
        if (is_null($task)) {
            $output->writeln('Unable to cancel, task '.$input->getArgument('id').' does not exist');
            return self::RETURN_CODE_TASK_DOES_NOT_EXIST;
        }
        
        $cancellationResult = $this->getWorkerTaskCancellationService()->cancel($task);
        
        if ($cancellationResult === 200) {
            return self::RETURN_CODE_OK;
        }
        
        if ($cancellationResult === -1) {
            $output->writeln('Cancellation request failed, task is in wrong state (currently:'.$task->getState().')');
            return self::RETURN_CODE_FAILED_DUE_TO_WRONG_STATE;            
        }        
        
        if ($this->isHttpStatusCode($cancellationResult)) {
            $output->writeln('Cancellation request failed, HTTP response '.$cancellationResult);
        } else {
            $output->writeln('Cancellation request failed, CURL error '.$cancellationResult);
        }
        
        return $cancellationResult;
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
     * @return \SimplyTestable\ApiBundle\Services\WorkerTaskCancellationService
     */    
    private function getWorkerTaskCancellationService() {
        return $this->getContainer()->get('simplytestable.services.workertaskcancellationservice');
    }
}