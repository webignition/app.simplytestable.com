<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class TooManyRedirectsTest extends BaseSimplyTestableTestCase
{
    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://www.example.com/';

    public function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 301\nLocation: " . self::SOURCE_URL,
            "HTTP/1.0 301\nLocation: " . self::SOURCE_URL,
            "HTTP/1.0 301\nLocation: " . self::SOURCE_URL,
            "HTTP/1.0 301\nLocation: " . self::SOURCE_URL,
            "HTTP/1.0 301\nLocation: " . self::EFFECTIVE_URL,
            "HTTP/1.0 301\nLocation: " . self::EFFECTIVE_URL,
            "HTTP/1.0 200 OK"
        )));
    }

    public function testFullSiteTestResolvesToEffectiveUrl()
    {
        $job = $this->createJobFactory()->create([
            JobFactory::KEY_SITE_ROOT_URL => self::SOURCE_URL,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertEquals(self::EFFECTIVE_URL, $job->getWebsite()->getCanonicalUrl());
    }
}
