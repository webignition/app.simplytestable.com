<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\Prepare\HttpErrorCases\RetrievingFeedContent;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

abstract class FeedTest extends BaseSimplyTestableTestCase
{
    public function setUp()
    {
        parent::setUp();

        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create();
        $jobFactory->resolve($job);

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            "HTTP/1.1 200 OK\nContent-Type: text/html; charset=UTF-8\n\n<!DOCTYPE html><html><head>" . $this->getFeedLink() . "</head><body></body></html>",
            'HTTP/1.0 ' . $this->getTestStatusCode(),
        )));

        $this->getJobPreparationService()->prepare($job);
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $job->getState());
    }

    abstract protected function getFeedLink();

    public function test400()
    {
    }

    public function test404()
    {
    }

    public function test500()
    {
    }

    public function test503()
    {
    }

    private function getTestStatusCode()
    {
        return (int)  str_replace('test', '', $this->getName());
    }
}