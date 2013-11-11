<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\ListAction;

class ExcludeByStateTest extends AbstractListTest {      
    
    public function testExcludeCrawlJobs() {
        $jobs = array();
        $jobs[] = $this->getJobService()->getById($this->createJobAndGetId('http://one.example.com', null, 'single url'));
        $jobs[] = $this->getJobService()->getById($this->createJobAndGetId('http://two.example.com', null, 'single url'));
        $jobs[] = $this->getJobService()->getById($this->createJobAndGetId('http://three.example.com', null, 'single url'));
        
        $jobs[0]->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($jobs[0]);
        
        $jobs[1]->setState($this->getJobService()->getRejectedState());
        $this->getJobService()->persistAndFlush($jobs[0]);        

        $listObject = json_decode($this->getJobController('listAction', array(), array(
            'exclude-states' => array(
                'rejected',
                'queued'
            )
        ))->listAction(count($jobs))->getContent());
        
        $this->assertEquals(1, count($listObject));
        $this->assertEquals($jobs[0]->getId(), $listObject[0]->id);      
    }    
    
}


