<?php

namespace Tests\ApiBundle\Command;

use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class WebsiteServiceTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider getDataProvider
     *
     * @param string $url
     * @param string $expectedWebsiteCanonicalUrl
     */
    public function testGet($url, $expectedWebsiteCanonicalUrl)
    {
        $websiteService = $this->container->get(WebSiteService::class);
        $website = $websiteService->get($url);

        $this->assertInstanceOf(Website::class, $website);
        $this->assertEquals($expectedWebsiteCanonicalUrl, $website->getCanonicalUrl());
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            'incomplete domain name' => [
                'url' => 'http://foo',
                'expectedWebsiteCanonicalUrl' => 'http://foo/',
            ],
            'unix-like local path' => [
                'url' => '/home/users/foo',
                'expectedWebsiteCanonicalUrl' => '/home/users/foo',
            ],
            'windows-like local path' => [
                'url' => 'c:\Users\foo\Desktop\file.html',
                'expectedWebsiteCanonicalUrl' => 'c:\Users\foo\Desktop\file.html',
            ],
            'not a valid url' => [
                'url' => 'vertical-align:top',
                'expectedWebsiteCanonicalUrl' => 'vertical-align:top',
            ],
            'complete http' => [
                'url' => 'http://example.com/',
                'expectedWebsiteCanonicalUrl' => 'http://example.com/',
            ],
            'complete https' => [
                'url' => 'https://example.com/',
                'expectedWebsiteCanonicalUrl' => 'https://example.com/',
            ],
        ];
    }
}
