<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction;

class ListTest extends AbstractListTest {
    
    public function testListIncludesUrlCount() {
        $this->getJobService()->getById($this->createResolveAndPrepareJob('http://one.example.com', null, 'single url'));        
        $list = json_decode($this->getJobListController('listAction')->listAction(1)->getContent());                
        $this->assertEquals(1, $list->jobs[0]->url_count);     
    }
    
}


