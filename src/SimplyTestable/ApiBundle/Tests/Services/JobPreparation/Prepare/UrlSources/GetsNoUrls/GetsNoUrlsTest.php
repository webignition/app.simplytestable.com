<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources;

use SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources\UrlSourcesTest;

class GetsNoUrlsTest extends UrlSourcesTest {
    
    public function testRobotsTxt_False_SitemapXml_False_SitemapTxt_False_Rss_False_Atom_False_GetsNoUrls() {}
    public function testRobotsTxt_True_SitemapXml_False_SitemapTxt_False_Rss_False_Atom_False_GetsNoUrls() {}
    
    protected function getExpectedJobState() {
        return $this->getJobService()->getFailedNoSitemapState();
    }     
}