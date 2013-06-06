<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Command\BaseCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;

class ReduceTaskCountOnLegacyPublicUserJobsCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const TASK_REMOVAL_GROUP_SIZE = 1000;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:reduce-task-count-on-legacy-public-user-jobs')
            ->setDescription('Reduce task count on legacy public user jobs to conform to plan constraints')
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Run through the process without writing any data')
            ->addOption('job-ids-to-ignore', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of job ids to ignore')
            ->setHelp(<<<EOF
Reduce task count on legacy public user jobs to conform to plan constraints.
EOF
        );     
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        $this->input = $input;
        
        if ($this->isDryRun()) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }
        
        $publicUser = $this->getUserService()->getPublicUser();
        $publicUserPlan = $this->getUserAccountPlanService()->getForUser($publicUser);
        $urlLimit = $publicUserPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit();
        
        $output->writeln('<info>Public user urls_per_job limit is: '.$urlLimit.'</info>');        
        
        if ($this->hasJobIdsToIgnore()) {
            $output->writeln('<info>Ignoring jobs: '.  implode(',', $this->getJobIdsToIgnore()).'</info>');
        }        
        
        $output->writeln('');
        
        $output->write('Finding public user jobs to check ... ');
        
        $jobStatesToExclude = array();
        $jobStatesToExclude[] = $this->getJobService()->getFailedNoSitemapState();
        $jobStatesToExclude[] = $this->getJobService()->getRejectedState();        
        
        $jobIdsToCheck = $this->getJobService()->getEntityRepository()->getIdsByUserAndTypeAndNotStates($publicUser, $this->getJobTypeService()->getByName('full site'), $jobStatesToExclude);
        
        $output->writeln(count($jobIdsToCheck).' found');
        
        $totalTasksRemovedCount = 0;
        $completedJobCount = 0;

        foreach ($jobIdsToCheck as $jobId) {            
            $completedJobCount++;
            
            if ($this->isJobIdIgnored($jobId)) {
                continue;
            }
            
            $output->write('Checking job ['.$jobId.'] ['.$completedJobCount.' of '.count($jobIdsToCheck).'] ... ');
            
            $job = $this->getJobService()->getById($jobId);            
            $urlCount = $this->getTaskService()->getEntityRepository()->findUrlCountByJob($job);
            
            if ($urlCount <= $urlLimit) {
                $output->writeln('ok');
                $this->getJobService()->getEntityManager()->detach($job);
                continue;
            }
            
            if (!$this->hasPlanUrlLimitReachedAmmendment($job)) {
                $this->getJobUserAccountPlanEnforcementService()->setUser($publicUser);
                $this->getJobService()->addAmmendment($job, 'plan-url-limit-reached:discovered-url-count-' . $urlCount, $this->getJobUserAccountPlanEnforcementService()->getJobUrlLimitConstraint());                
                $this->getJobService()->getEntityManager()->flush();
            }

            $taskCount = $this->getTaskService()->getEntityRepository()->getCountByJob($job);
            $output->write('Has ['.$urlCount.'] urls and ['.$taskCount.'] tasks, ');

            $urlsToKeep = $this->getUrlsToKeep($job, $urlLimit);
            
            $taskIdsToRemove = $this->getTaskService()->getEntityRepository()->getIdsByJobAndUrlExclusionSet($job, $urlsToKeep);
            $output->write('removing ['.count($taskIdsToRemove).'] tasks ');
            
            $taskRemovalGroups = $this->getTaskRemovalGroups($taskIdsToRemove);
            
            foreach ($taskRemovalGroups as $taskIdGroupIndex => $taskIdGroup) {                
                
                foreach ($taskIdGroup as $taskId) {
                    /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */ 
                    $task = $this->getTaskService()->getById($taskId);
                    $this->getTaskService()->getEntityManager()->remove($task);
                    $this->getTaskService()->getEntityManager()->detach($task);                    
                }
                
                if (!$this->isDryRun()) {                        
                    $this->getTaskService()->getEntityManager()->flush();
                }                  
                
                $ratioCompleted = ($taskIdGroupIndex + 1) / count($taskRemovalGroups);                
                
                if ($ratioCompleted > 0 && $ratioCompleted <= 0.25) {
                    $output->write('<fg=green>.</fg=green>');
                }
                
                if ($ratioCompleted > 0.25 && $ratioCompleted <= 0.50) {
                    $output->write('<fg=blue>.</fg=blue>');
                }
                
                if ($ratioCompleted > 0.50 && $ratioCompleted <= 0.75) {
                    $output->write('<fg=yellow>.</fg=yellow>');
                }                
                
                if ($ratioCompleted > 0.75) {
                    $output->write('<fg=red>.</fg=red>');
                }
            }
            
            $output->writeln('');
            $totalTasksRemovedCount += count($taskIdsToRemove);            
        }
        
        $output->writeln('');
        $output->writeln('');
        $output->writeln('<info>============================================</info>');
        $output->writeln('');
        $output->writeln('Tasks removed: ['.$totalTasksRemovedCount.']');
        
        
        return self::RETURN_CODE_OK;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    private function hasPlanUrlLimitReachedAmmendment(Job $job) {
        if (is_null($job->getAmmendments()) || $job->getAmmendments()->count() === 0) {
            return false;
        }        
        
        foreach ($job->getAmmendments() as $ammendment) {
            /* @var $ammendment Ammendment */
            if (substr_count($ammendment->getReason(), 'plan-url-limit-reached')) {
                return true;
            }
        }
        
        return false;
    }
    
    private function getTaskRemovalGroups($taskIdsToRemove) {
        $taskRemovalGroups = array();
        
        if (count($taskIdsToRemove) <= self::TASK_REMOVAL_GROUP_SIZE) {
            $taskRemovalGroups[] = $taskIdsToRemove;
            return $taskRemovalGroups;
        }    
        
        $currentRemovalGroup = array();
                
        foreach ($taskIdsToRemove as $taskIdIndex => $taskId) {
            if ($taskIdIndex % self::TASK_REMOVAL_GROUP_SIZE === 0) {
                $taskRemovalGroups[] = $currentRemovalGroup;
                $currentRemovalGroup = array();
            }
            
            $currentRemovalGroup[] = $taskId;
        }
        
        return $taskRemovalGroups;
    }
    
    
    /**
     * 
     * @param int $jobId
     * @return boolean
     */
    private function isJobIdIgnored($jobId) {
        return in_array($jobId, $this->getJobIdsToIgnore());
    }
    
    private function getUrlsToKeep(Job $job, $limit) {
        $urls = $this->getTaskService()->getEntityRepository()->findUrlsByJob($job);
        $tempUrlsToKeep = array_slice($urls, 0, $limit);
        $urlsToKeep = array();
        
        foreach ($tempUrlsToKeep as $urlRecord) {
            $urlsToKeep[] = $urlRecord['url'];
        }
        
        return $urlsToKeep;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function isDryRun() {        
        return $this->input->getOption('dry-run') == 'true';
    }   
    
    
    /**
     * 
     * @return array
     */
    private function hasJobIdsToIgnore() {        
        return !is_null($this->input->getOption('job-ids-to-ignore'));
    } 
    
    
    /**
     * 
     * @return array
     */
    private function getJobIdsToIgnore() {        
        if (!$this->hasJobIdsToIgnore()) {
            return array();
        }    
                
        return explode(',', $this->input->getOption('job-ids-to-ignore'));
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
     * @return \SimplyTestable\ApiBundle\Services\TaskService
     */    
    private function getTaskService() {
        return $this->getContainer()->get('simplytestable.services.taskservice');
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
     * @return \SimplyTestable\ApiBundle\Services\UserService
     */    
    private function getUserService() {
        return $this->getContainer()->get('simplytestable.services.userservice');
    }        
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */    
    private function getUserAccountPlanService() {
        return $this->getContainer()->get('simplytestable.services.useraccountplanservice');
    } 

    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService
     */        
    private function getJobUserAccountPlanEnforcementService() {
        return $this->getContainer()->get('simplytestable.services.jobUserAccountPlanEnforcementService');
    }
}