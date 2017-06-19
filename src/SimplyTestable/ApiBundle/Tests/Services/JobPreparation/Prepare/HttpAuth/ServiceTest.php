<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HttpAuth;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class ServiceTest extends BaseSimplyTestableTestCase
{
    const HTTP_AUTH_USERNAME_KEY = 'http-auth-username';
    const HTTP_AUTH_PASSWORD_KEY = 'http-auth-password';
    const HTTP_AUTH_USERNAME = 'foo';
    const HTTP_AUTH_PASSWORD = 'bar';

    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();

        $this->queueResolveHttpFixture();

        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $this->job = $jobFactory->create([
            JobFactory::KEY_PARAMETERS => [
                self::HTTP_AUTH_USERNAME_KEY => self::HTTP_AUTH_USERNAME,
                self::HTTP_AUTH_PASSWORD_KEY => self::HTTP_AUTH_PASSWORD,
            ],
        ]);
        $jobFactory->resolve($this->job);

        $this->queueHttpFixtures(
            $this->buildHttpFixtureSet(
                $this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')
            )
        );

        $this->getJobPreparationService()->prepare($this->job);
    }

    public function testParametersAreSetOnTasks()
    {
        foreach ($this->job->getTasks() as $task) {
            $decodedParameters = json_decode($task->getParameters());
            $this->assertTrue(isset($decodedParameters->{self::HTTP_AUTH_USERNAME_KEY}));
            $this->assertEquals(self::HTTP_AUTH_USERNAME, $decodedParameters->{self::HTTP_AUTH_USERNAME_KEY});
            $this->assertTrue(isset($decodedParameters->{self::HTTP_AUTH_PASSWORD_KEY}));
            $this->assertEquals(self::HTTP_AUTH_PASSWORD, $decodedParameters->{self::HTTP_AUTH_PASSWORD_KEY});
        }
    }

    public function testWithUrlsCollectedViaSitemapViaRobotsTxt()
    {
        $baseRequest = $this
            ->getJobPreparationService()
            ->getWebsiteService()
            ->getSitemapFinder()
            ->getConfiguration()
            ->getBaseRequest();

        $this->assertGreaterThan(0, $this->job->getTasks()->count());
        $this->assertEquals(self::HTTP_AUTH_USERNAME, $baseRequest->getUsername());
        $this->assertEquals(self::HTTP_AUTH_PASSWORD, $baseRequest->getPassword());
    }


    public function testWithUrlsCollectedSitemapViaGuessingPath()
    {
        $baseRequest = $this
            ->getJobPreparationService()
            ->getWebsiteService()
            ->getSitemapFinder()
            ->getConfiguration()
            ->getBaseRequest();

        $this->assertGreaterThan(0, $this->job->getTasks()->count());
        $this->assertEquals(self::HTTP_AUTH_USERNAME, $baseRequest->getUsername());
        $this->assertEquals(self::HTTP_AUTH_PASSWORD, $baseRequest->getPassword());
    }

    public function testWithUrlsCollectedViaRssFeed()
    {
        $baseRequest = $this
            ->getJobPreparationService()
            ->getWebsiteService()
            ->getSitemapFinder()
            ->getConfiguration()
            ->getBaseRequest();

        $this->assertGreaterThan(0, $this->job->getTasks()->count());
        $this->assertEquals(self::HTTP_AUTH_USERNAME, $baseRequest->getUsername());
        $this->assertEquals(self::HTTP_AUTH_PASSWORD, $baseRequest->getPassword());
    }
}
