<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TaskPreProcessor\LinkIntegrity;

class SetExcludedUrlsParameterWhenStoringPartialOutputTest extends ExcludedUrlsTest {
    
    public function setUp() {
        parent::setUp();
        
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $this->tasks->get(1)->getId()
        ));          
    }       
    
    public function test1thTaskHasCorrectExcludedUrls() {
        $this->assertEquals(array(
            'excluded-urls' => array(
                'http://example.com/three',
                'http://example.com/two'
                
            )
        ), json_decode($this->tasks->get(1)->getParameters(), true));        
    }
    
}
