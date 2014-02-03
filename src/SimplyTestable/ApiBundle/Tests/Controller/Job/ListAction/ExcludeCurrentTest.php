<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\ListAction;

class ExcludeCurrentTest extends AbstractListTest {      
    
    public function testExcludeCurrent() {
        $jobs = array();        
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://one.example.com', null, 'single url'));
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://two.example.com', null, 'single url'));
        $jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob('http://three.example.com', null, 'single url'));
        
        $jobs[0]->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($jobs[0]);

        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-current' => '1'
        ))->listAction(count($jobs))->getContent());
        
        $this->assertEquals(1, count($list->jobs));
        $this->assertEquals($jobs[0]->getId(), $list->jobs[0]->id);     
    } 
    
    
    public function testExcludesCrawlingJobs() {
        $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
        
        $list = json_decode($this->getJobController('listAction', array(
            'user' => $this->getTestUser()->getEmail()
        ), array(
            'exclude-current' => '1'
        ))->listAction()->getContent());
        
        $this->assertEquals(0, count($list->jobs));      
    }
    
}


