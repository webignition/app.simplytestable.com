<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class TrimToRootUrlTest extends BaseSimplyTestableTestCase
{
    const EFFECTIVE_URL = 'http://www.example.com/relative/path.html';
    const ROOT_URL = 'http://www.example.com/';

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);

        $this->queueHttpFixtures([
            HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://www.example.com/'),
            HttpFixtureFactory::createFoundRedirectResponse('/relative/path.html'),
            HttpFixtureFactory::createSuccessResponse(),
        ]);
    }

    public function testFullSiteTestResolvesToRootUrl()
    {
        $job = $this->jobFactory->create();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertEquals(self::ROOT_URL, $job->getWebsite()->getCanonicalUrl());
    }

    public function testSingleUrlTestResolvesToEffectiveUrl()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertEquals(self::EFFECTIVE_URL, $job->getWebsite()->getCanonicalUrl());
    }
}
