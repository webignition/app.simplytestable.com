<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompleteAllWithNoIncompleteTasksCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;
    const RETURN_CODE_NO_MATCHING_JOBS = 2;
    
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
        
        if ($this->isDryRun()) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }
        
        $output->write('Finding matching jobs (unfinished with more than zero tasks all finished) ... ');
        
        $jobs = $this->getJobService()->getUnfinishedJobsWithTasksAndNoIncompleteTasks();
        
        // Exclude crawl jobs
        foreach ($jobs as $jobIndex => $job) {
            if ($job->getType() === $this->getJobTypeService()->getCrawlType()) {
                unset($jobs[$jobIndex]);
            }
        }

        if (count($jobs) === 0) {
            $output->writeln('None found. Done.');
            return self::RETURN_CODE_NO_MATCHING_JOBS;
        }
        
        $output->writeln(count($jobs) . ' found.');
        $output->writeln('Marking jobs as completed ... ');
        
        foreach ($jobs as $job) {            
            $output->writeln('['.$job->getId().'] ');
            
            if ($this->isDryRun() === false) {
                $this->getJobService()->complete($job);
            }
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
     * @return int
     */
    protected function isDryRun() {
        return $this->input->getOption('dry-run') == 'true';
    }    
}