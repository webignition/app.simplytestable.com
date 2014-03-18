<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TaskPreProcessor\LinkIntegrity;

abstract class ExcludedUrlsTest extends PreProcessorTest {

    protected function getCompletedTaskOutput() {
        return array(
            array(
                'context' => '<a href="http://example.com/one">Example One</a>',
                'state' => 404,
                'type' => 'http',
                'url' => 'http://example.com/one'
            ),
            array(
                'context' => '<a href="http://example.com/two">Example Two</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/two'
            ),
            array(
                'context' => '<a href="http://example.com/three">Example Three</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/three'
            )            
        );
    }
    
    public function test1thTaskHasOutput() {        
        $this->assertTrue($this->tasks->get(1)->hasOutput());              
    }  
}
