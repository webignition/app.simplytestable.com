<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\MetaRedirect\DifferentUrl;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class DifferentUrlTest extends BaseSimplyTestableTestCase
{
    const SOURCE_URL = 'http://example.com/';

    protected function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . $this->getRedirectUrl() . "\"></head></html>",
            "HTTP/1.0 200 OK",
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . $this->getRedirectUrl() . "\"></head></html>",
            "HTTP/1.0 200 OK"
        )));

        $jobFactory = new JobFactory($this->container);

        $fullSiteJob = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::SOURCE_URL,
        ]);

        $this->getJobWebsiteResolutionService()->resolve($fullSiteJob);
        $this->assertEquals($this->getRootUrl(), $fullSiteJob->getWebsite()->getCanonicalUrl());

        $singleUrlJob = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::SOURCE_URL,
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
        ]);
        $this->getJobWebsiteResolutionService()->resolve($singleUrlJob);
        $this->assertEquals($this->getEffectiveUrl(), $singleUrlJob->getWebsite()->getCanonicalUrl());
    }

    abstract protected function getRedirectUrl();
    abstract protected function getEffectiveUrl();
    abstract protected function getRootUrl();
}
