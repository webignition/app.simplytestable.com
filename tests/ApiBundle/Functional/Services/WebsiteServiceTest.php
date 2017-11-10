<?php

namespace Tests\ApiBundle\Command;

use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class WebsiteServiceTest extends AbstractBaseTestCase
{
    public function testFetch()
    {
        $websiteService = $this->container->get(WebSiteService::class);

        $url = 'http://example.com/';
        $website = $websiteService->get($url);

        $this->assertInstanceOf(Website::class, $website);
        $this->assertEquals($url, $website->getCanonicalUrl());
    }
}
