<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources;

use SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources\UrlSourcesTest;

class GetsRssUrlsTest extends UrlSourcesTest {
    
    public function testRobotsTxt_False_SitemapXml_False_SitemapTxt_False_Rss_True_Atom_False_GetsRssUrls() {}
    public function testRobotsTxt_False_SitemapXml_False_SitemapTxt_False_Rss_True_Atom_True_GetsRssUrls() {}    
    public function testRobotsTxt_True_SitemapXml_False_SitemapTxt_False_Rss_True_Atom_False_GetsRssUrls() {}
    public function testRobotsTxt_True_SitemapXml_False_SitemapTxt_False_Rss_True_Atom_True_GetsRssUrls() {} 
    
    protected function getExpectedJobState() {
        return $this->getJobService()->getQueuedState();
    }     
    
}