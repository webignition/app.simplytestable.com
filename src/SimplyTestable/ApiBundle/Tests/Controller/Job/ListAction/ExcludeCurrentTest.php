<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\ListAction;

class ExcludeCurrentTest extends AbstractListTest {      
    
    public function testExcludeCurrent() {
        $jobs = array();
        $jobs[] = $this->getJobService()->getById($this->createJobAndGetId('http://one.example.com', null, 'single url'));
        $jobs[] = $this->getJobService()->getById($this->createJobAndGetId('http://two.example.com', null, 'single url'));
        $jobs[] = $this->getJobService()->getById($this->createJobAndGetId('http://three.example.com', null, 'single url'));
        
        $jobs[0]->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($jobs[0]);

        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-current' => '1'
        ))->listAction(count($jobs))->getContent());
        
        $this->assertEquals(1, count($list->jobs));
        $this->assertEquals($jobs[0]->getId(), $list->jobs[0]->id);     
    } 
    
    
    public function testExcludesCrawlingJobs() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');
        $canonicalUrl = 'http://example.com';
        
        $this->createAndPrepareJob($canonicalUrl, $user->getEmail());
        
        $list = json_decode($this->getJobController('listAction', array(
            'user' => $user->getEmail()
        ), array(
            'exclude-current' => '1'
        ))->listAction()->getContent());
        
        $this->assertEquals(0, count($list->jobs));      
    }
    
}


