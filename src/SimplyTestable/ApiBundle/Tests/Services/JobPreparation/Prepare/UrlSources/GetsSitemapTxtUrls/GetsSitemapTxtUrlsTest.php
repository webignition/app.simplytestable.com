<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources;

use SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources\UrlSourcesTest;

class GetsSitemapTxtUrlsTest extends UrlSourcesTest {
    
    public function testRobotsTxt_True_SitemapXml_False_SitemapTxt_True_Rss_False_Atom_False_GetsSitemapTxtUrls() {}
    public function testRobotsTxt_True_SitemapXml_False_SitemapTxt_True_Rss_False_Atom_True_GetsSitemapTxtUrls() {}    
    public function testRobotsTxt_True_SitemapXml_False_SitemapTxt_True_Rss_True_Atom_False_GetsSitemapTxtUrls() {}
    public function testRobotsTxt_True_SitemapXml_False_SitemapTxt_True_Rss_True_Atom_True_GetsSitemapTxtUrls() {}    
    public function testRobotsTxt_False_SitemapXml_False_SitemapTxt_True_Rss_False_Atom_False_GetsSitemapTxtUrls() {}    
    public function testRobotsTxt_False_SitemapXml_False_SitemapTxt_True_Rss_False_Atom_True_GetsSitemapTxtUrls() {}
    public function testRobotsTxt_False_SitemapXml_False_SitemapTxt_True_Rss_True_Atom_False_GetsSitemapTxtUrls() {}
    public function testRobotsTxt_False_SitemapXml_False_SitemapTxt_True_Rss_True_Atom_True_GetsSitemapTxtUrls() {} 
    
    protected function getExpectedJobState() {
        return $this->getJobService()->getQueuedState();
    }      
    
}