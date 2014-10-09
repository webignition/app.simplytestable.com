<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteAction\UrlDiscovery;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

abstract class CompleteDiscoveryTest extends BaseControllerJsonTestCase {


    /**
     * @var CrawlJobContainer
     */
    private  $crawlJobContainer;


    /**
     * @var Job
     */
    protected $crawlJob;


    /**
     * @var Job
     */
    private $parentJob;


    /**
     * @var \stdClass
     */
    private $jobCssTaskParametersObject;


    /**
     * @var \stdClass
     */
    private $jobJsTaskParametersObject;


    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
            self::DEFAULT_CANONICAL_URL,
            null,
            null,
            ['CSS validation', 'JS static analysis'],
            [
                'CSS validation' => [
                    'ignore-common-cdns' => 1
                ],
                'JS static analysis' => [
                    'ignore-common-cdns' => 1
                ]
            ],
            [
                'http-auth-username' => 'example',
                'http-auth-password' => 'password'
            ]
        ));

        $this->crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->crawlJob = $this->crawlJobContainer->getCrawlJob();
        $this->parentJob = $this->crawlJobContainer->getParentJob();

        $this->getCrawlJobContainerService()->prepare($this->crawlJobContainer);

        $this->performControllerAction();

        foreach ($this->parentJob->getTasks() as $task) {
            /* @var $task Task */
            if ($task->getType()->equals($this->getTaskTypeService()->getByName('CSS validation'))) {
                $this->jobCssTaskParametersObject = json_decode($task->getParameters());
            }

            if ($task->getType()->equals($this->getTaskTypeService()->getByName('JS static analysis'))) {
                $this->jobJsTaskParametersObject = json_decode($task->getParameters());
            }
        }
    }


    abstract protected function performControllerAction();

    public function testMarksCrawlJobAsComplete() {
        $this->assertEquals($this->getJobService()->getCompletedState(), $this->crawlJob->getState());
    }

    public function testParentJobIsQueued() {
        $this->assertEquals($this->getJobService()->getQueuedState(), $this->parentJob->getState());
    }

    public function testParentTaskCount() {
        $expectedTaskCount = count($this->getCrawlJobContainerService()->getDiscoveredUrls($this->crawlJobContainer, true)) * $this->crawlJobContainer->getParentJob()->getRequestedTaskTypes()->count();
        $this->assertEquals($expectedTaskCount, $this->parentJob->getTasks()->count());
    }

    public function testParentTaskGenericParametersArePassedToTasks() {
        $jobParametersObject = json_decode($this->parentJob->getParameters());
        $taskParametersObject = json_decode($this->parentJob->getTasks()->first()->getParameters());

        foreach ($jobParametersObject as $key => $value) {
            $this->assertTrue(isset($taskParametersObject->{$key}));
            $this->assertEquals($value, $taskParametersObject->{$key});
        }
    }

    public function testParentJobCssTaskParametersDomainsToIgnore() {
        $this->assertTrue(isset($this->jobCssTaskParametersObject->{'domains-to-ignore'}));
    }

    public function testParentJobJsTaskParametersDomainsToIgnore() {
        $this->assertTrue(isset($this->jobJsTaskParametersObject->{'domains-to-ignore'}));
    }

    public function testCssValidationDomainsToIgnoreArePassedToParentJobTasks() {
        $this->assertEquals($this->container->getParameter('css-validation-domains-to-ignore'), $this->jobCssTaskParametersObject->{'domains-to-ignore'});
    }

    public function testJsStaticAnalysisDomainsToIgnoreArePassedToParentJobTasks() {
        $this->assertEquals($this->container->getParameter('js-static-analysis-domains-to-ignore'), $this->jobJsTaskParametersObject->{'domains-to-ignore'});
    }

    public function testResqueTasksNotifyJobIsCreated() {
        $this->assertFalse($this->getResqueQueueService()->isEmpty(
            'tasks-notify'
        ));
    }

}


