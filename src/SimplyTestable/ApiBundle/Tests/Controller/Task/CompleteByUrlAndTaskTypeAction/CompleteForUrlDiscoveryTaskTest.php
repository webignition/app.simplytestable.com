<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteByUrlAndTaskTypeAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CompleteForUrlDiscoveryTaskTest extends BaseControllerJsonTestCase {

    public function testCompleteForTaskDiscoveringUrlsDoesNotMarkJobAsComplete() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());        
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));    
        $this->createWorker();
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();

        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));

        $this->assertEquals('task-in-progress', $task->getState()->getName());

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());

        $this->assertTrue($crawlJobContainer->getCrawlJob()->getState()->equals($this->getJobService()->getInProgressState()));
    }
    
    public function testCompleteForTaskDiscoveringNoUrlsMarksJobAsComplete() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));

        $this->assertEquals('task-in-progress', $task->getState()->getName());

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());

        $this->assertTrue($crawlJobContainer->getCrawlJob()->getState()->equals($this->getJobService()->getCompletedState()));
    }


    public function testParentJobIsRestartedOnceCrawlJobCompletesForSingleUrl() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));

        $this->assertEquals('task-in-progress', $task->getState()->getName());

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());

        $expectedTaskCount = count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)) * $crawlJobContainer->getParentJob()->getRequestedTaskTypes()->count();

        $this->assertTrue($this->getJobService()->isQueued($crawlJobContainer->getParentJob()));
        $this->assertEquals($expectedTaskCount, $crawlJobContainer->getParentJob()->getTasks()->count());
    }

    public function testParentJobIsRestartedOnceCrawlJobCompletesForMultipleUrls() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));

        $this->assertEquals('task-in-progress', $task->getState()->getName());

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, 10)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());

        $expectedTaskCount = count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)) * $crawlJobContainer->getParentJob()->getRequestedTaskTypes()->count();

        $this->assertTrue($this->getJobService()->isQueued($crawlJobContainer->getParentJob()));
        $this->assertEquals($expectedTaskCount, $crawlJobContainer->getParentJob()->getTasks()->count());
    }

    public function testParentJobIsRestartedOnceCrawlJobReachesAccountPlanUrlConstraint() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));

        $this->assertEquals('task-in-progress', $task->getState()->getName());

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, 7)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->get(1);
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, 7, 7)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
       ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());

        $expectedTaskCount = count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)) * $crawlJobContainer->getParentJob()->getRequestedTaskTypes()->count();

        $this->assertTrue($this->getJobService()->isCompleted($crawlJobContainer->getCrawlJob()));
        $this->assertTrue($this->getJobService()->isQueued($crawlJobContainer->getParentJob()));
        $this->assertEquals($expectedTaskCount, $crawlJobContainer->getParentJob()->getTasks()->count());
    }


    public function testParentJobParametersArePassedToTasksWhenCrawlJobCompletes() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
                self::DEFAULT_CANONICAL_URL,
                null,
                null,
                array('HTML validation'),
                null,
                array(
                    'http-auth-username' => 'example',
                    'http-auth-password' => 'password'
                )
        ));

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));

        $this->assertEquals('task-in-progress', $task->getState()->getName());

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());

        $this->assertEquals($job->getParameters(), $job->getTasks()->first()->getParameters());
    }


    public function testUrlDiscoveryTaskErrorIsIgnoredWhenCollectingUrls() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));

        $this->assertEquals('task-in-progress', $task->getState()->getName());

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '{"messages":[{"message":"Unauthorized","messageId":"http-retrieval-401","type":"error"}]}',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 1,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());

        $expectedTaskCount = count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)) * $crawlJobContainer->getParentJob()->getRequestedTaskTypes()->count();

        $this->assertTrue($this->getJobService()->isQueued($crawlJobContainer->getParentJob()));
        $this->assertEquals($expectedTaskCount, $crawlJobContainer->getParentJob()->getTasks()->count());
    }

    public function testPostCrawlPrepareSetsPrefinedDomainsToIgnoreForCssValidation() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
                self::DEFAULT_CANONICAL_URL,
                null,
                null,
                array('CSS validation'),
                array(
                    'CSS validation' => array(
                        'ignore-common-cdns' => 1
                    )
                )
        ));

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));

        $this->assertEquals('task-in-progress', $task->getState()->getName());

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());

        $jobTaskParametersObject = json_decode($job->getTasks()->first()->getParameters());

        $this->assertTrue(isset($jobTaskParametersObject->{'domains-to-ignore'}));
        $this->assertEquals($this->container->getParameter('css-validation-domains-to-ignore'), $jobTaskParametersObject->{'domains-to-ignore'});
    }


    public function testPostCrawlPrepareSetsPrefinedDomainsToIgnoreForJsStaticAnalysis() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(
                self::DEFAULT_CANONICAL_URL,
                null,
                null,
                array('JS static analysis'),
                array(
                    'JS static analysis' => array(
                        'ignore-common-cdns' => 1
                    )
                )
        ));

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));

        $this->assertEquals('task-in-progress', $task->getState()->getName());

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());

        $jobTaskParametersObject = json_decode($job->getTasks()->first()->getParameters());

        $this->assertTrue(isset($jobTaskParametersObject->{'domains-to-ignore'}));
        $this->assertEquals($this->container->getParameter('js-static-analysis-domains-to-ignore'), $jobTaskParametersObject->{'domains-to-ignore'});
    }
}


