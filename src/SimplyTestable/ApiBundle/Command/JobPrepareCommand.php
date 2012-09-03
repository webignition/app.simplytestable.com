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
        
        $job = $this->getContainer()->get('simplytestable.services.jobservice')->getById(
            (int)$input->getArgument('id')
        );      
        
        if ($job->getState()->getName() != JobService::STARTING_STATE) {
            return $this->getLogger()->info("simplytestable:job:prepare: nothing to do, job has a state of [".$job->getState()->getName()."]");
        }
        
        $job->setNextState();        
        
        $urls = $this->getWebsiteService()->getUrls($job->getWebsite());
        $requestedTaskTypes = $job->getRequestedTaskTypes();
        $newTaskState = $this->getNewTaskState();

        $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();

        $jobCount = 0;
        
        foreach ($urls as $url) {                
            foreach ($requestedTaskTypes as $taskType) {
                $jobCount++;
                
                $task = new Task();
                $task->setJob($job);
                $task->setType($taskType);
                $task->setUrl($url);
                $task->setState($newTaskState);
                
                $entityManager->persist($task);                             
            }
        }
        
        $job->setNextState();
        
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $job->setTimePeriod($timePeriod);   
        
        $entityManager->persist($job);
        $entityManager->flush(); 
        
        foreach ($job->getTasks() as $task) {
            $this->getContainer()->get('simplytestable.services.resqueQueueService')->add(
                'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
                'task-assign',
                array(
                    'id' => $task->getId()
                )
            );             
        }
        
        $this->getLogger()->info("simplytestable:job:prepare: queued up [".$jobCount."] tasks covering [".count($urls)."] urls and [".count($requestedTaskTypes)."] task types");
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    private function getNewTaskState() {
        return $this->getContainer()->get('simplytestable.services.stateservice')->find('task-queued');
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
     * @return SimplyTestable\ApiBundle\Services\ResqueJobFactoryService 
     */
    private function getResqueJobFactoryService() {
        return $this->getContainer()->get('simplytestable.services.ResqueJobFactoryService');
    }
}