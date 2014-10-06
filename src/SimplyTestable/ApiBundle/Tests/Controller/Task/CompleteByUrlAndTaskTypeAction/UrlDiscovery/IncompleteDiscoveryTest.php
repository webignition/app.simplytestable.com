<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteByUrlAndTaskTypeAction\UrlDiscovery;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class IncompleteDiscoveryTest extends BaseControllerJsonTestCase {

    /**
     * @var Job
     */
    private $crawlJob;


    /**
     * @var Job
     */
    private $parentJob;


    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->crawlJob = $crawlJobContainer->getCrawlJob();
        $this->parentJob = $crawlJobContainer->getParentJob();

        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
    }

    public function testDoesNotMarkCrawlJobAsComplete() {
        $this->assertNotEquals($this->getJobService()->getCompletedState(), $this->crawlJob->getState());
    }

}


