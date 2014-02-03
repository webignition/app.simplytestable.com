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
                    $this->getResqueQueueService()->add(
                        'SimplyTestable\ApiBundle\Resque\Job\TaskAssignmentSelectionJob',
                        'task-assignment-selection'
                    );
                }                
            } else {
                $this->getResqueQueueService()->add(
                    'SimplyTestable\ApiBundle\Resque\Job\JobPrepareJob',
                    'job-prepare',
                    array(
                        'id' => $job->getId()
                    )                
                );
                
                
            }           
        } catch (\SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException $websiteResolutionException) {            
            if ($websiteResolutionException->isJobInWrongStateException()) {
                return self::RETURN_CODE_CANNOT_RESOLVE_IN_WRONG_STATE;
            }
        }
        
        return 0;
        
        var_dump("cp01");
        exit();

        
        

//        
//        try {
//             $this->getJobPreparationService()->prepare($job);
//            
//            if ($this->getResqueQueueService()->isEmpty('task-assignment-selection')) {
//                $this->getResqueQueueService()->add(
//                    'SimplyTestable\ApiBundle\Resque\Job\TaskAssignmentSelectionJob',
//                    'task-assignment-selection'
//                );             
//            }
//
//            $this->getLogger()->info("simplytestable:job:prepare: queued up [".$job->getTasks()->count()."] tasks covering [".$job->getUrlCount()."] urls and [".count($job->getRequestedTaskTypes())."] task types");
//            
//            return self::RETURN_CODE_OK;
//        } catch (\SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception $jobPreparationServiceException) {
//            if ($jobPreparationServiceException->isJobInWrongStateException()) {
//                $this->getLogger()->info("simplytestable:job:prepare: nothing to do, job has a state of [".$job->getState()->getName()."]");
//                return self::RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE;
//            }
//            
//            throw $jobPreparationServiceException;
//        } catch (\Exception $e) {
//            var_dump(get_class($e), $e);
//            exit();
//        }
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
     * @return \SimplyTestable\ApiBundle\Services\ResqueQueueService
     */        
    private function getResqueQueueService() {
        return $this->getContainer()->get('simplytestable.services.resqueQueueService');
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