<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\ListAction;

class ExcludeByStateTest extends AbstractListTest {      
    
    public function testExcludeByState() {
        $jobs = array();        
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://one.example.com', null, 'single url'));
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://two.example.com', null, 'single url'));
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://three.example.com', null, 'single url'));
        
        $jobs[0]->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($jobs[0]);
        
        $jobs[1]->setState($this->getJobService()->getRejectedState());
        $this->getJobService()->persistAndFlush($jobs[0]);        

        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-states' => array(
                'rejected',
                'queued'
            )
        ))->listAction(count($jobs))->getContent());
        
        $this->assertEquals(1, count($list->jobs));
        $this->assertEquals($jobs[0]->getId(), $list->jobs[0]->id);      
    }    
    
}


