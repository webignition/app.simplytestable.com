<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\ListAction\ExcludeCurrent;

use SimplyTestable\ApiBundle\Tests\Controller\Job\Job\ListAction\AbstractListTest;

class ExcludeCurrentTest extends AbstractListTest {      
    
    public function testExcludeCurrent() {
        $jobs = array();
        
        foreach ($this->getJobService()->getIncompleteStates() as $incompleteState) {
            $job = $this->getJobService()->getById($this->createJobAndGetId('http://incomplete-' . $incompleteState->getName(). '.example.com/'));
            $job->setState($incompleteState);
            $this->getJobService()->persistAndFlush($job);
            $jobs[] = $job;
        }
        
        foreach ($this->getJobService()->getFinishedStates() as $finishedState) {
            $job = $this->getJobService()->getById($this->createJobAndGetId('http://finished-' . $finishedState->getName(). '.example.com/'));
            $job->setState($finishedState);
            $this->getJobService()->persistAndFlush($job);
            $jobs[] = $job;
        }
        
        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-current' => '1'
        ))->listAction(count($jobs))->getContent());        
        
        $this->assertEquals(count($this->getJobService()->getFinishedStates()), count($list->jobs));    
        
        foreach ($list->jobs as $jobDetails) {
            $this->assertTrue(substr_count($jobDetails->website, 'http://finished') === 1);
        }
    }
    
}


