<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources;

use SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\UrlSources\UrlSourcesTest;

class GetsSitemapXmlAndSitemapTxtUrlsTest extends UrlSourcesTest {
    
    public function testRobotsTxt_False_SitemapXml_True_SitemapTxt_True_Rss_False_Atom_False_GetsSitemapXmlUrlsAndSitemapTxtUrls() {}         
    public function testRobotsTxt_False_SitemapXml_True_SitemapTxt_True_Rss_False_Atom_True_GetsSitemapXmlUrlsAndSitemapTxtUrls() {}     
    public function testRobotsTxt_False_SitemapXml_True_SitemapTxt_True_Rss_True_Atom_False_GetsSitemapXmlUrlsAndSitemapTxtUrls() {}     
    public function testRobotsTxt_False_SitemapXml_True_SitemapTxt_True_Rss_True_Atom_True_GetsSitemapXmlUrlsAndSitemapTxtUrls() {}
    public function testRobotsTxt_True_SitemapXml_True_SitemapTxt_True_Rss_False_Atom_False_GetsSitemapXmlUrlsAndSitemapTxtUrls() {}        
    public function testRobotsTxt_True_SitemapXml_True_SitemapTxt_True_Rss_False_Atom_True_GetsSitemapXmlUrlsAndSitemapTxtUrls() {}
    public function testRobotsTxt_True_SitemapXml_True_SitemapTxt_True_Rss_True_Atom_False_GetsSitemapXmlUrlsAndSitemapTxtUrls() {}
    public function testRobotsTxt_True_SitemapXml_True_SitemapTxt_True_Rss_True_Atom_True_GetsSitemapXmlUrlsAndSitemapTxtUrls() {} 
    
    protected function getExpectedJobState() {
        return $this->getJobService()->getQueuedState();
    }      
    
}