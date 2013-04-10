<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

class PrepareRetrievesUrlsFromCorrectSourcesTest extends PrepareJobAndPostAssertionsTest {    
    
    public function testNoRobotsTxtNoSitemapXmlNoRssNoAtomGetsNoUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            0,
            'no-sitemap',
            array()
        );        
    }    
    
    
    public function testNoRobotsTxtNoSitemapXmlNoRssHasAtomGetsAtomUrls() {        
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            1,
            'queued',
            array(
                'http://example.com/2003/12/13/atom03'
            )
        );
    } 
    
    public function testNoRobotsTxtNoSitemapXmlHasRssNoAtomGetsRssUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            1,
            'queued',
            array(
                'http://example.com/url/'
            )
        );      
    } 
    
    
    public function testNoRobotsTxtHasSitemapXmlGetsSitemapXmlUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            3,
            'queued',
            array(
                'http://www.example.com/?id=who',
                'http://www.example.com/?id=what',
                'http://www.example.com/?id=how'
            )
        );
    }
    
    
    public function testNoRobotsTxtHasSitemapTxtGetsSitemapTxtUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            3,
            'queued',
            array(
                'http://www.example.com/text/one/',
                'http://www.example.com/text/two/',
                'http://www.example.com/text/three/'
            )
        );
    }    
    

    public function testHasRobotsTxtNoSitemapGetsNoRssNoAtomGetsNoUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            0,
            'no-sitemap',
            array()
        );
    } 
    
    public function testHasRobotsTxtNoSitemapGetsNoRssHasAtomGetsAtomUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            1,
            'queued',
            array(
                'http://example.com/2003/12/13/atom03'
            )
        );
    }    
    
    public function testHasRobotsTxtNoSitemapGetsHasRssNoAtomGetsRssUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            1,
            'queued',
            array(
                'http://example.com/url/'
            )
        ); 
    }    
    
    public function testHasRobotsTxtHasSitemapXmlGetsSitemapXmlUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            3,
            'queued',
            array(
                'http://www.example.com/?id=who',
                'http://www.example.com/?id=what',
                'http://www.example.com/?id=how'
            )
        );
    }  
    
    
    public function testHasRobotsTxtHasSitemapTxtGetsSitemapTxtUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            3,
            'queued',
            array(
                'http://www.example.com/text/one/',
                'http://www.example.com/text/two/',
                'http://www.example.com/text/three/'
            )
        );
    }    
    
    public function testHasRobotsTxtHasSitemapXmlHasSitemapTxtGetsSitemapXmlAndSitemapTxtUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            6,
            'queued',
            array(
                'http://www.example.com/?id=who',
                'http://www.example.com/?id=what',
                'http://www.example.com/?id=how',             
                'http://www.example.com/text/one/',
                'http://www.example.com/text/two/',
                'http://www.example.com/text/three/'
            )
        );
    }
}
