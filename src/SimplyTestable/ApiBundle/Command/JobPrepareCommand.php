<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\TimePeriod;

use webignition\NormalisedUrl\NormalisedUrl;

class JobPrepareCommand extends BaseCommand
{
    /**
     *
     * @var SimplyTestable\ApiBundle\Services\WebSiteService
     */
    private $websiteService;
    
    /**
     *
     * @var string
     */
    private $httpFixturePath;
    
    
    /**
     *
     * @var array
     */
    private $processedUrls = array();
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:prepare')
            ->setDescription('Prepare a set of tasks for a given job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to prepare')
            ->addArgument('http-fixture-path', InputArgument::OPTIONAL, 'path to HTTP fixture data when testing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $job Job */
        /* @var $taskType TaskType */
        /* @var $task Task */
        /* @var $websiteService \SimplyTestable\ApiBundle\Services\WebsiteService */
        /* @var $entityManager \Doctrine\ORM\EntityManager */
        
        $this->getLogger()->info("simplytestable:job:prepare running for job [".$input->getArgument('id')."]");
        
        if ($input->hasArgument('http-fixture-path') && $input->getArgument('http-fixture-path') != '') {
            $this->getLogger()->debug("simplytestable:job:prepare: using fixure path [".$input->getArgument('http-fixture-path')."]");
            $this->httpFixturePath = $input->getArgument('http-fixture-path');
        }
        
        $job = $this->getJobService()->getById((int)$input->getArgument('id'));
        
        if (!$this->getJobService()->isNew($job)) {
            return $this->getLogger()->info("simplytestable:job:prepare: nothing to do, job has a state of [".$job->getState()->getName()."]");
        }
        
        $job->setNextState();  
        
        $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
        $entityManager->persist($job);
        $entityManager->flush();         
        
        $urls = $this->getWebsiteService()->getUrls($job->getWebsite());
        
        if ($urls === false || count($urls) == 0) {
            $job->setState($this->getJobService()->getNoSitemapState());
            $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
            $entityManager->persist($job);
            $entityManager->flush();
            return $this->getLogger()->info("simplytestable:job:prepare: no sitemap found for [".(string)$job->getWebsite()."]");
        }
        
        $requestedTaskTypes = $job->getRequestedTaskTypes();
        $newTaskState = $this->getTaskService()->getQueuedState();

        $jobCount = 0;
        
        foreach ($urls as $url) {                
            $comparatorUrl = new NormalisedUrl($url);
            if (!$this->isProcessedUrl($comparatorUrl)) {
                foreach ($requestedTaskTypes as $taskType) {
                    $jobCount++;

                    $task = new Task();
                    $task->setJob($job);
                    $task->setType($taskType);
                    $task->setUrl($url);
                    $task->setState($newTaskState);

                    $entityManager->persist($task);                             
                }
                
                $this->processedUrls[] = (string)$comparatorUrl;
            }
        }
        
        $job->setNextState();
        
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $job->setTimePeriod($timePeriod);   
        
        $entityManager->persist($job);
        $entityManager->flush(); 
        
        if ($this->getResqueQueueService()->isEmpty('task-assignment-selection')) {
            $this->getResqueQueueService()->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignmentSelectionJob',
                'task-assignment-selection'
            );             
        }
        
        $this->getLogger()->info("simplytestable:job:prepare: queued up [".$jobCount."] tasks covering [".count($urls)."] urls and [".count($requestedTaskTypes)."] task types");
    }
    
    
    /**
     * 
     * @param \webignition\NormalisedUrl\NormalisedUrl $url
     * @return boolean
     */
    private function isProcessedUrl(NormalisedUrl $url) {
        return in_array((string)$url, $this->processedUrls);
    }
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\WebSiteService
     */
    private function getWebsiteService() {
        if (is_null($this->websiteService)) {
            $this->websiteService = $this->getContainer()->get('simplytestable.services.websiteservice');
            
            if (!is_null($this->httpFixturePath)) {
                $this->websiteService->getHttpClient()->getStoredResponseList()->setFixturesPath($this->httpFixturePath);
            }
        }
        
        return $this->websiteService;
    }
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\JobService
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
     * @return SimplyTestable\ApiBundle\Services\TaskService
     */    
    private function getTaskService() {
        return $this->getContainer()->get('simplytestable.services.taskservice');
    }    
}