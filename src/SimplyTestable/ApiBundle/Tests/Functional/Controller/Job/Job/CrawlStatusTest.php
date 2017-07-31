<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job;

use Guzzle\Http\Message\Response;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

class CrawlStatusTest extends BaseControllerJsonTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }

    public function testWithQueuedCrawlJob()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $jobObject = json_decode($this->getJobController('statusAction', array(
            'user' => $user->getEmail()
        ))->statusAction((string)$job->getWebsite(), $job->getId())->getContent());

        $this->assertEquals('queued', $jobObject->crawl->state);
        $this->assertEquals(10, $jobObject->crawl->limit);
    }

    public function testWithInProgressCrawlJob()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')
            )
        );

        $this->createWorker();

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();

        $this->assertEquals(0, $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        )));

        $this->assertEquals($this->getTaskService()->getInProgressState(), $task->getState());

        $user = $task->getJob()->getUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);

        $urlCountToDiscover = (int)round(
            $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit() / 2
        );

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(
                $job->getWebsite()->getCanonicalUrl(),
                $urlCountToDiscover
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
        ]);

        $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
        $taskController->completeAction();

        $statusActionResponse = $this->getJobController('statusAction')->statusAction(
            (string)$job->getWebsite(),
            $job->getId()
        );

        $jobObject = json_decode($statusActionResponse->getContent());

        $this->assertEquals('in-progress', $jobObject->crawl->state);
        $this->assertEquals(1, $jobObject->crawl->processed_url_count);
        $this->assertEquals(6, $jobObject->crawl->discovered_url_count);
        $this->assertEquals(10, $jobObject->crawl->limit);
    }

    public function testCrawlJobIdIsExposed()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $statusActionResponse = $this->getJobController('statusAction')->statusAction(
            (string)$job->getWebsite(),
            $job->getId()
        );

        $jobObject = json_decode($statusActionResponse->getContent());

        $this->assertEquals('queued', $jobObject->crawl->state);
        $this->assertEquals(10, $jobObject->crawl->limit);
        $this->assertNotNull($jobObject->crawl->id);
    }

    public function testGetForPublicJobOwnedByNonPublicUserByPublicUser()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $this->getJobController('setPublicAction')->setPublicAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $jobObject = json_decode($this->fetchJobResponse($job)->getContent());

        $this->assertTrue($job->getIsPublic());
        $this->assertTrue(isset($jobObject->crawl));
    }

    public function testGetForPublicJobOwnedByNonPublicUserByNonPublicUser()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $this->getJobController('setPublicAction')->setPublicAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $jobObject = json_decode($this->fetchJobResponse($job)->getContent());

        $this->assertTrue($job->getIsPublic());
        $this->assertTrue(isset($jobObject->crawl));
    }

    public function testGetForPublicJobOwnedByNonPublicUserByDifferentNonPublicUser()
    {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');

        $this->getUserService()->setUser($user1);
        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user1,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $this->getJobController('setPublicAction')->setPublicAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $this->getUserService()->setUser($user2);
        $jobObject = json_decode($this->fetchJobResponse($job)->getContent());

        $this->assertTrue($job->getIsPublic());
        $this->assertTrue(isset($jobObject->crawl));
    }

    public function testGetForPrivateJobOwnedByNonPublicUserByPublicUser()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->assertEquals(403, $this->fetchJobResponse($job)->getStatusCode());
    }

    public function testGetForPrivateJobOwnedByNonPublicUserByNonPublicUser()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $jobObject = json_decode($this->fetchJobResponse($job)->getContent());

        $this->assertTrue(isset($jobObject->crawl));
    }

    public function testGetForPrivateJobOwnedByNonPublicUserByDifferentNonPublicUser()
    {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password');

        $this->getUserService()->setUser($user1);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user1,
        ]);

        $this->getUserService()->setUser($user2);
        $this->assertEquals(403, $this->fetchJobResponse($job)->getStatusCode());
    }

    public function testGetJobOwnerCrawlLimitForPublicJobOwnedByPrivateUser()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'agency'
        );

        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        $accountPlanUrlLimit = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit();

        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $this->getJobController('setPublicAction')->setPublicAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $jobObject = json_decode($this->fetchJobResponse($job)->getContent());

        $this->assertTrue($job->getIsPublic());
        $this->assertEquals($accountPlanUrlLimit, $jobObject->crawl->limit);
    }
}
