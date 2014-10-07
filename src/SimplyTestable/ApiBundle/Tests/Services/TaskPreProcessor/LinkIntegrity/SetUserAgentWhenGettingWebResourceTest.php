<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TaskPreProcessor\LinkIntegrity;

class SetUserAgentWhenGettingWebResourceTest extends ExcludedUrlsTest {
    
    public function setUp() {
        parent::setUp();
        
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $this->tasks->get(1)->getId()
        ));          
    }       
    
    public function testHttpRequestHistoryUserAgent() {
        $this->assertEquals(
            'ST Link integrity task pre-processor',
            (string)$this->getHttpClientService()->getHistoryPlugin()->getAll()[3]['request']->getHeader('user-agent')
        );
    }
    
}
