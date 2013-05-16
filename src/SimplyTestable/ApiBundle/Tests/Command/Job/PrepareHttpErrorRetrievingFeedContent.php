<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

class PrepareHttpErrorRetrievingFeedContent extends PrepareJobAndPostAssertionsTest {    
    
    public function testHttpClientErrorRetrievingRssContent() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            0,
            'failed-no-sitemap',
            array()
        );        
    }   
    
    
    public function testHttpServerErrorRetrievingRssContent() {        
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            0,
            'failed-no-sitemap',
            array()
        );  
    }    

    
    public function testHttpClientErrorRetrievingAtomContent() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            0,
            'failed-no-sitemap',
            array()
        );        
    }    
    
    
    public function testHttpServerErrorRetrievingAtomContent() {        
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            0,
            'failed-no-sitemap',
            array()
        );  
    }

}
