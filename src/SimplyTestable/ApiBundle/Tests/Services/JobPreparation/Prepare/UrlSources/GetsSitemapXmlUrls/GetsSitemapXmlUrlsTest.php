<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources;

use SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources\UrlSourcesTest;

class GetsSitemapXmlUrlsTest extends UrlSourcesTest {
    
    public function testRobotsTxt_False_SitemapXml_True_SitemapTxt_False_Rss_False_Atom_False_GetsSitemapXmlUrls() {}    
    public function testRobotsTxt_False_SitemapXml_True_SitemapTxt_False_Rss_False_Atom_True_GetsSitemapXmlUrls() {}
    public function testRobotsTxt_False_SitemapXml_True_SitemapTxt_False_Rss_True_Atom_False_GetsSitemapXmlUrls() {}
    public function testRobotsTxt_False_SitemapXml_True_SitemapTxt_False_Rss_True_Atom_True_GetsSitemapXmlUrls() {}
    public function testRobotsTxt_True_SitemapXml_True_SitemapTxt_False_Rss_False_Atom_False_GetsSitemapXmlUrls() {}
    public function testRobotsTxt_True_SitemapXml_True_SitemapTxt_False_Rss_False_Atom_True_GetsSitemapXmlUrls() {}
    public function testRobotsTxt_True_SitemapXml_True_SitemapTxt_False_Rss_True_Atom_False_GetsSitemapXmlUrls() {}
    public function testRobotsTxt_True_SitemapXml_True_SitemapTxt_False_Rss_True_Atom_True_GetsSitemapXmlUrls() {}
    
    protected function getExpectedJobState() {
        return $this->getJobService()->getQueuedState();
    }      
    
}