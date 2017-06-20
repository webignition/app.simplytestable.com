<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\MetaRedirect\SameUrl;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class SameUrlTest extends BaseSimplyTestableTestCase
{
    const SOURCE_URL = 'http://example.com/';

    public function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->getTestHttpFixtures());

        $job = $this->createJobFactory()->create([
            JobFactory::KEY_SITE_ROOT_URL => self::SOURCE_URL,
        ]);

        $this->getJobWebsiteResolutionService()->resolve($job);
        $this->assertEquals(self::SOURCE_URL, $job->getWebsite()->getCanonicalUrl());
    }

    abstract protected function getTestHttpFixtures();
}
