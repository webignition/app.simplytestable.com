<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

class PrepareHttpErrorRetrievingRootWebPageTest extends PrepareJobAndPostAssertionsTest {    

    
    public function testHttpClientErrorRetrievingRootWebPage() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            0,
            'no-sitemap',
            array()
        );        
    }    
    
    
    public function testHttpServerErrorRetrievingRootWebPage() {        
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            0,
            'no-sitemap',
            array()
        );  
    }

}
