<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;

class JobMarkCompletedCommand extends BaseCommand
{
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:markcompleted')
            ->setDescription('Mark a job as being completed')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to mark as completed')
            ->setHelp(<<<EOF
Mark a job as being completed
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $job Job */
        /* @var $taskType TaskType */
        /* @var $task Task */
        /* @var $websiteService \SimplyTestable\ApiBundle\Services\WebsiteService */
        /* @var $entityManager \Doctrine\ORM\EntityManager */
        
        $this->getLogger()->info("simplytestable:job:markcompleted running for job [".$input->getArgument('id')."]");        
        $job = $this->getJobService()->getById((int)$input->getArgument('id'));
        
        if (!$job->getState()->equals($this->getJobService()->getInProgressState())) {
            return $this->getLogger()->info("simplytestable:job:markcompleted: nothing to do, job has a state of [".$job->getState()->getName()."]");
        }
        
        if ($this->getJobService()->hasIncompleteTasks($job)) {
            return $this->getLogger()->info("simplytestable:job:markcompleted: can't mark as complete, job has outstanding incomplete tasks");
        }
        
        $this->getJobService()->complete($job);
    }
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\JobService
     */    
    private function getJobService() {
        return $this->getContainer()->get('simplytestable.services.jobservice');
    }
}