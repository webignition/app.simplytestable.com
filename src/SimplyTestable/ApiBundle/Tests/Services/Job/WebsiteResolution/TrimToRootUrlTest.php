<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class TrimToRootUrlTest extends BaseSimplyTestableTestCase
{
//    const SOURCE_URL = 'http://example.com/';
    const EFFECTIVE_URL = 'http://www.example.com/relative/path.html';
    const ROOT_URL = 'http://www.example.com/';

    public function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.1 301\nLocation: http://www.example.com/",
            "HTTP/1.1 302 Found\nLocation: /relative/path.html",
            "HTTP/1.0 200"
        )));
    }

    public function testFullSiteTestResolvesToRootUrl()
    {
        $job = $this->createJobFactory()->create();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertEquals(self::ROOT_URL, $job->getWebsite()->getCanonicalUrl());
    }

    public function testSingleUrlTestResolvesToEffectiveUrl()
    {
        $job = $this->createJobFactory()->create([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertEquals(self::EFFECTIVE_URL, $job->getWebsite()->getCanonicalUrl());
    }
}
