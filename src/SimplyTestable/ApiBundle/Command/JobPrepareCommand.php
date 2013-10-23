<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class JobPrepareCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    const RETURN_CODE_NO_URLS = 3;
    const RETURN_CODE_UNROUTABLE = 4;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:prepare')
            ->setDescription('Prepare a set of tasks for a given job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to prepare')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        /* @var $job Job */
        /* @var $taskType TaskType */
        /* @var $task Task */
        /* @var $websiteService \SimplyTestable\ApiBundle\Services\WebsiteService */
        /* @var $entityManager \Doctrine\ORM\EntityManager */
        
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }           
        
        $this->getLogger()->info("simplytestable:job:prepare running for job [".$input->getArgument('id')."]");
        
        $job = $this->getJobService()->getById((int)$input->getArgument('id'));
        
        foreach ($job->getRequestedTaskTypes() as $taskType) {
            /* @var $taskType TaskType */
            $taskTypeParameterDomainsToIgnoreKey = strtolower(str_replace(' ', '-', $taskType->getName())) . '-domains-to-ignore';            

            if ($this->getContainer()->hasParameter($taskTypeParameterDomainsToIgnoreKey)) {
                $this->getJobPreparationService()->setPredefinedDomainsToIgnore($taskType, $this->getContainer()->getParameter($taskTypeParameterDomainsToIgnoreKey));
            }
        }
        
        try {
            $jobPreparationResult = $this->getJobPreparationService()->prepare($job);
        } catch (\Exception $e) {
            var_dump(get_class($e), $e);
            exit();
        }
        
        switch ($jobPreparationResult) {
            case 0:
                // ok
                break;
            
            case self::RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE:
                $this->getLogger()->info("simplytestable:job:prepare: nothing to do, job has a state of [".$job->getState()->getName()."]");
                return self::RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE;
                
            case self::RETURN_CODE_NO_URLS:
                $this->getLogger()->info("simplytestable:job:prepare: no sitemap found for [".(string)$job->getWebsite()."]");
                return self::RETURN_CODE_OK;
                
            case self::RETURN_CODE_UNROUTABLE:
                $this->getLogger()->info("simplytestable:job:prepare: unroutable [".(string)$job->getWebsite()."]");
                return self::RETURN_CODE_UNROUTABLE;                
                
        }
        
        if ($this->getResqueQueueService()->isEmpty('task-assignment-selection')) {
            $this->getResqueQueueService()->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignmentSelectionJob',
                'task-assignment-selection'
            );             
        }
        
        $this->getLogger()->info("simplytestable:job:prepare: queued up [".$job->getTasks()->count()."] tasks covering [".$job->getUrlCount()."] urls and [".count($job->getRequestedTaskTypes())."] task types");
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
     * @return SimplyTestable\ApiBundle\Services\ResqueQueueService
     */        
    private function getResqueQueueService() {
        return $this->getContainer()->get('simplytestable.services.resqueQueueService');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
     */     
    private function getJobPreparationService() {
        return $this->getContainer()->get('simplytestable.services.jobpreparationservice');
    }
}