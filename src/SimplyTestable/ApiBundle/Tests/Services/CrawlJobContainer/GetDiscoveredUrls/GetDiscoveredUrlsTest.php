<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer\GetDiscoveredUrls;

use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

class GetDiscoveredUrlsTest extends BaseSimplyTestableTestCase {

    public function testGetDiscoveredUrls() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));

        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/", "http:\/\/example.com\/two/", "http:\/\/example.com\/three/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
        ]);

        $this->createTaskController($taskCompleteRequest)->completeAction();

        $this->getCrawlJobContainerService()->processTaskResults($task);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->get(1);
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/", "http:\/\/example.com\/four/", "http:\/\/example.com\/five/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
        ]);

        $this->createTaskController($taskCompleteRequest)->completeAction();

        $this->getCrawlJobContainerService()->processTaskResults($task);

        $this->assertEquals(array(
            'http://example.com/',
            'http://example.com/one/',
            'http://example.com/two/',
            'http://example.com/three/',
            'http://example.com/four/',
            'http://example.com/five/'
        ), $this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer));
    }

}
