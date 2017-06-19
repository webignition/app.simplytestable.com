<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HappyPath;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class HappyPathTest extends BaseSimplyTestableTestCase
{
    /**
      * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();

        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $this->job = $jobFactory->create([
            JobFactory::KEY_TEST_TYPES => [
                'html validation',
                'css validation',
                'js static analysis',
                'link integrity',
            ],
        ]);
        $jobFactory->resolve($this->job);

        $this->getHttpClientService()->queueFixtures($this->buildHttpFixtureSet($this->getFixtureMessages()));
        $this->getJobPreparationService()->prepare($this->job);
    }

    abstract protected function getFixtureMessages();

    public function testPreparationThrowsNoExceptions()
    {
    }

    public function testStateIsQueued()
    {
        $this->assertEquals($this->getJobService()->getQueuedState(), $this->job->getState());
    }

    public function testHasStartTime()
    {
        $this->assertNotNull($this->job->getTimePeriod());
        $this->assertNotNull($this->job->getTimePeriod()->getStartDateTime());
    }

    public function testHasNotEndTime()
    {
        $this->assertNull($this->job->getTimePeriod()->getEndDateTime());
    }

    public function testHasTasks()
    {
        $this->assertGreaterThan(0, $this->job->getTasks()->count());
    }

    public function testTaskUrls()
    {
        $expectedTaskUrls = array(
            'http://example.com/0/',
            'http://example.com/0/',
            'http://example.com/0/',
            'http://example.com/0/',
            'http://example.com/1/',
            'http://example.com/1/',
            'http://example.com/1/',
            'http://example.com/1/',
            'http://example.com/2/',
            'http://example.com/2/',
            'http://example.com/2/',
            'http://example.com/2/',
        );

        foreach ($this->job->getTasks() as $index => $task) {
            $this->assertEquals($expectedTaskUrls[$index], $task->getUrl(), 'Task at index ' . $index. ' does not have URL "'.$expectedTaskUrls[$index].'"');
        }
    }

    public function testCurlOptionsAreSetOnAllRequests()
    {
        $this->assertSystemCurlOptionsAreSetOnAllRequests();
    }

    public function testTaskStates()
    {
        foreach ($this->job->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getQueuedState(), $task->getState());
        }
    }
}
