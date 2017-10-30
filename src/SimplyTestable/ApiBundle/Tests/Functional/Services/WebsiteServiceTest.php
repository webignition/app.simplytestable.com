<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

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
