<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResolveWebsiteCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_CANNOT_RESOLVE_IN_WRONG_STATE = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:resolve')
            ->setDescription('Resolve a job\'s canonical url to be sure where we are starting off')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to process')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        } 
        
        $job = $this->getJobService()->getById((int)$input->getArgument('id'));
        
        try {            
            $this->getJobWebsiteResolutionService()->resolve($job);          
        } catch (\SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException $websiteResolutionException) {            
            if ($websiteResolutionException->isJobInWrongStateException()) {
                return self::RETURN_CODE_CANNOT_RESOLVE_IN_WRONG_STATE;
            }
        }
        
        if ($this->getJobService()->isFinished($job)) {
            return self::RETURN_CODE_OK;
        }
        
        if ($job->getType()->equals($this->getJobTypeService()->getSingleUrlType())) {             

            foreach ($job->getRequestedTaskTypes() as $taskType) {
                /* @var $taskType TaskType */
                $taskTypeParameterDomainsToIgnoreKey = strtolower(str_replace(' ', '-', $taskType->getName())) . '-domains-to-ignore';

                if ($this->getContainer()->hasParameter($taskTypeParameterDomainsToIgnoreKey)) {
                    $this->getJobPreparationService()->setPredefinedDomainsToIgnore($taskType, $this->getContainer()->getParameter($taskTypeParameterDomainsToIgnoreKey));
                }
            }

            $this->getJobPreparationService()->prepare($job);

            if ($this->getResqueQueueService()->isEmpty('task-assignment-selection')) {
                $this->getResqueQueueService()->enqueue(
                    $this->getResqueJobFactoryService()->create(
                        'task-assignment-selection'
                    )
                );
            }                
        } else {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'job-prepare',
                    ['id' => $job->getId()]
                )
            );
        }         
        
        return 0;
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
     * @return \SimplyTestable\ApiBundle\Services\JobTypeService
     */    
    private function getJobTypeService() {
        return $this->getContainer()->get('simplytestable.services.jobtypeservice');
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
     * @return \SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService
     */     
    private function getJobWebsiteResolutionService() {
        return $this->getContainer()->get('simplytestable.services.jobwebsiteresolutionservice');
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
     */     
    private function getJobPreparationService() {
        return $this->getContainer()->get('simplytestable.services.jobpreparationservice');
    }     
}