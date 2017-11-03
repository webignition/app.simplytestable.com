<?php

namespace Tests\ApiBundle\Command;

use SimplyTestable\ApiBundle\Entity\WebSite;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class WebsiteServiceTest extends AbstractBaseTestCase
{
    public function testFetch()
    {
        $websiteService = $this->container->get('simplytestable.services.websiteservice');

        $url = 'http://example.com/';
        $website = $websiteService->fetch($url);

        $this->assertInstanceOf(Website::class, $website);
        $this->assertEquals($url, $website->getCanonicalUrl());
    }
}
