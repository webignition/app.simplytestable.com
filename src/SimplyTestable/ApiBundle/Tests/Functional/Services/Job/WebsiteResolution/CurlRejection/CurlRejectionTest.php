<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\CurlRejection;

use SimplyTestable\ApiBundle\Tests\Factory\CurlExceptionFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class CurlRejectionTest extends BaseSimplyTestableTestCase
{
    const SOURCE_URL = 'http://example.com/';

    protected $job;

    protected function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures([
            CurlExceptionFactory::create('', $this->getTestStatusCode()),
        ]);

        $jobFactory = new JobFactory($this->container);
        $this->job = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::SOURCE_URL,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->resolveJob($this->job->getWebsite()->getCanonicalUrl(), $this->job->getId());
    }

    public function test7()
    {
    }

    public function test28()
    {
    }

    public function test52()
    {
    }

    /**
     * @return int
     */
    protected function getTestStatusCode()
    {
        return (int)str_replace('test', '', $this->getName());
    }
}
