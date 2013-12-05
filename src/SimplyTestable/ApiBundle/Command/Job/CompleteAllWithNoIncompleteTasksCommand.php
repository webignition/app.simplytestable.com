<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use SimplyTestable\ApiBundle\Command\BaseCommand;

//use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//
use SimplyTestable\ApiBundle\Entity\Job\Job;
//use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
//use SimplyTestable\ApiBundle\Services\JobService;
//use SimplyTestable\ApiBundle\Entity\Task\Task;
//use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
//use SimplyTestable\ApiBundle\Entity\TimePeriod;
//
//use webignition\NormalisedUrl\NormalisedUrl;

class CompleteAllWithNoIncompleteTasksCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;
    const RETURN_CODE_NO_IN_PROGRESS_JOBS = 2;
    const RETURN_CODE_NO_IN_PROGRESS_JOBS_WITHOUT_INCOMPLETE_TASKS = 3;
    
    /**
     *
     * @var InputInterface
     */
    protected $input;     
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:complete-all-with-no-incomplete-tasks')
            ->setDescription('Mark as completed all in-progress jobs that have no incomplete tasks')
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Run through the process without writing any data')                
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {        
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }
        
        $this->input = $input;        
        
        $this->getJobListService()->setExcludeStates(array(
            $this->getJobService()->getCompletedState(),
            $this->getJobService()->getQueuedState(),
            $this->getJobService()->getPreparingState(),
            $this->getJobService()->getStartingState(),
            $this->getJobService()->getCancelledState(),
            $this->getJobService()->getFailedNoSitemapState(),
            $this->getJobService()->getRejectedState(),
        ));
       
        $this->getJobListService()->setExcludeTypes(array(
            $this->getJobTypeService()->getCrawlType()
        ));
        
        if ($this->isDryRun()) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }
        
        $output->write('Finding in-progress jobs ... ');
        
        $inProgressJobs = $this->getJobListService()->get();
        if (count($inProgressJobs) === 0) {
            $output->writeln('None found. Done.');
            return self::RETURN_CODE_NO_IN_PROGRESS_JOBS;
        }
        
        $output->writeln(count($inProgressJobs). ' job'.(count($inProgressJobs) === 1 ? '' : 's').' in progress.');

        $output->writeln('Filtering to jobs with no incomplete tasks ...');
        
        foreach ($inProgressJobs as $jobIndex => $job) {
            /* @var $job Job */
            if ($this->getJobService()->hasIncompleteTasks($job)) {
                $output->writeln('Removing job ['.$job->getId().']');
                unset($inProgressJobs[$jobIndex]);
            }
        }
        
        if (count($inProgressJobs) === 0) {
            $output->writeln('None found. Done.');
            return self::RETURN_CODE_NO_IN_PROGRESS_JOBS_WITHOUT_INCOMPLETE_TASKS;
        }        
        
        return 0;
        
        //$jobs = $this->ge
        
        $jobIds = $this->getJobService()->getEntityRepository()->getIdsByState($this->getJobService()->getStartingState());
        $output->writeln(count($jobIds).' new jobs to prepare');
        
        foreach ($jobIds as $jobId) {
            $output->writeln('Enqueuing prepare for job '.$jobId);
            $this->getResqueQueueService()->add(
                'SimplyTestable\ApiBundle\Resque\Job\JobPrepareJob',
                'job-prepare',
                array(
                    'id' => $jobId
                )                
            );             
        }
        
        return 0;
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
     * @return \SimplyTestable\ApiBundle\Services\JobService
     */    
    private function getJobService() {
        return $this->getContainer()->get('simplytestable.services.jobservice');
    }        
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobListService
     */    
    private function getJobListService() {
        return $this->getContainer()->get('simplytestable.services.joblistservice');
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
     * @return int
     */
    protected function isDryRun() {
        return $this->input->getOption('dry-run') == 'true';
    }    
}