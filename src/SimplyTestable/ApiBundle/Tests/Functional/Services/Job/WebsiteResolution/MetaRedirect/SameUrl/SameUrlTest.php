<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\MetaRedirect\SameUrl;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class SameUrlTest extends BaseSimplyTestableTestCase
{
    const SOURCE_URL = 'http://example.com/';

    protected function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->getTestHttpFixtures());

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::SOURCE_URL,
        ]);

        $this->getJobWebsiteResolutionService()->resolve($job);
        $this->assertEquals(self::SOURCE_URL, $job->getWebsite()->getCanonicalUrl());
    }

    abstract protected function getTestHttpFixtures();
}
