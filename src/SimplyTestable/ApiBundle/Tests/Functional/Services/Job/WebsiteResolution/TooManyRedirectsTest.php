<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class TooManyRedirectsTest extends BaseSimplyTestableTestCase
{
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://www.example.com/';

    protected function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures([
            HttpFixtureFactory::createMovedPermanentlyRedirectResponse(self::SOURCE_URL),
            HttpFixtureFactory::createMovedPermanentlyRedirectResponse(self::SOURCE_URL),
            HttpFixtureFactory::createMovedPermanentlyRedirectResponse(self::SOURCE_URL),
            HttpFixtureFactory::createMovedPermanentlyRedirectResponse(self::SOURCE_URL),
            HttpFixtureFactory::createMovedPermanentlyRedirectResponse(self::EFFECTIVE_URL),
            HttpFixtureFactory::createMovedPermanentlyRedirectResponse(self::EFFECTIVE_URL),
            HttpFixtureFactory::createSuccessResponse(),
        ]);
    }

    public function testFullSiteTestResolvesToEffectiveUrl()
    {
        $jobFactory = new JobFactory($this->container);

        $job = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::SOURCE_URL,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertEquals(self::EFFECTIVE_URL, $job->getWebsite()->getCanonicalUrl());
    }
}
