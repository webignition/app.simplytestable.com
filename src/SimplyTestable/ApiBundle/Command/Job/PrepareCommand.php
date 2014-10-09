<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class PrepareCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    
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
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'job-prepare',
                    ['id' => (int)$input->getArgument('id')]
                )
            );

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
            $this->getJobPreparationService()->prepare($job);

            if ($job->getTasks()->count()) {
                $this->getResqueQueueService()->enqueue(
                    $this->getResqueJobFactoryService()->create(
                        'tasks-notify'
                    )
                );
            } else {
                if ($this->getCrawlJobContainerService()->hasForJob($job)) {
                    $crawlJob = $this->getCrawlJobContainerService()->getForJob($job)->getCrawlJob();

                    $this->getResqueQueueService()->enqueue(
                        $this->getResqueJobFactoryService()->create(
                            'task-assign-collection',
                            ['ids' => $crawlJob->getTasks()->first()->getId()]
                        )
                    );
                }
            }

            $this->getLogger()->info("simplytestable:job:prepare: queued up [".$job->getTasks()->count()."] tasks covering [".$job->getUrlCount()."] urls and [".count($job->getRequestedTaskTypes())."] task types");
            
            return self::RETURN_CODE_OK;
        } catch (\SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception $jobPreparationServiceException) {            
            if ($jobPreparationServiceException->isJobInWrongStateException()) {
                $this->getLogger()->info("simplytestable:job:prepare: nothing to do, job has a state of [".$job->getState()->getName()."]");
                return self::RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE;
            }
            
            throw $jobPreparationServiceException;
        } catch (\Exception $e) {
            $this->getContainer()->get('logger')->error('JobPrepareCommand exception: [' . get_class($e) . ']');
            $this->getContainer()->get('logger')->error('JobPrepareCommand exception: job id [' . $job->getId() . ']');
        }
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
     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
     */     
    private function getJobPreparationService() {
        return $this->getContainer()->get('simplytestable.services.jobpreparationservice');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\CrawlJobContainerService
     */
    private function getCrawlJobContainerService() {
        return $this->getContainer()->get('simplytestable.services.crawljobcontainerservice');
    }
}