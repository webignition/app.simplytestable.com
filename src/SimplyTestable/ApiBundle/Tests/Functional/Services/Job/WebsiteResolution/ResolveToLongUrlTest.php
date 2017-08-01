<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class ResolveToLongUrlTest extends BaseSimplyTestableTestCase
{
    private $effectiveUrl = [
        'http://example.com/',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.1 301\nLocation: " . implode('', $this->effectiveUrl),
            "HTTP/1.0 200"
        )));
    }

    public function testTest()
    {
        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->create([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertEquals(implode('', $this->effectiveUrl), $job->getWebsite()->getCanonicalUrl());
    }
}
