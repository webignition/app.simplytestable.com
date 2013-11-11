<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\ListAction;

class ListTest extends AbstractListTest {
    
    public function testListIncludesUrlCount() {
        $this->getJobService()->getById($this->createJobAndGetId('http://one.example.com', null, 'single url'));     
        $listObject = json_decode($this->getJobController('listAction')->listAction(1)->getContent());        
        
        $this->assertEquals(1, $listObject[0]->url_count);     
    }
    
}


