<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class JobPrepareCommand extends ContainerAwareCommand
{
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
        
        $id = (int)$input->getArgument('id');
        $job = $this->getContainer()->get('simplytestable.services.jobservice')->getById($id);
        
        $websiteService = $this->getContainer()->get('simplytestable.services.websiteservice');
        $urls = $websiteService->getUrls($job->getWebsite());
        $requestedTaskTypes = $job->getRequestedTaskTypes();
        $newTaskState = $this->getNewTaskState();
        
        $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
        
        foreach ($urls as $url) {
            foreach ($requestedTaskTypes as $taskType) {
                $task = new Task();
                $task->setJob($job);
                $task->setType($taskType);
                $task->setUrl($url);
                $task->setState($newTaskState);
                
                $entityManager->persist($task);
            }
        }
        
        $job->setNextState();
        $entityManager->persist($job);
        
        $entityManager->flush();
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    private function getNewTaskState() {
        return $this->getContainer()->get('simplytestable.services.stateservice')->find('task-queued');
    }
}