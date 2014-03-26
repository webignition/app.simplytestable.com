<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HappyPath;

abstract class FromNewsFeedTest extends HappyPathTest {

    protected function getFixtureMessages() {
        return array(
            'HTTP/1.0 404', // No robots.txt
            'HTTP/1.0 404', // No sitemap.xml
            'HTTP/1.0 404', // No sitemap.txt
            $this->getRootWebPageFixture(),
            $this->getFeedFixture()
        );
    } 
    
    abstract protected function getRootWebPageFixture();
    abstract protected function getFeedFixture();   

}
