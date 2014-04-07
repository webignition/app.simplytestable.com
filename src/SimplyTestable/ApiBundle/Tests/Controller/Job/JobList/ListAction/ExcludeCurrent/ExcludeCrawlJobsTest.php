<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeCurrent;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\AbstractListTest;

class ExcludeCrawlJobsTest extends AbstractListTest {    
    
    public function testExcludesCrawlingJobs() {
        $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
        
        $list = json_decode($this->getJobListController('listAction', array(
            'user' => $this->getTestUser()->getEmail()
        ), array(
            'exclude-current' => '1'
        ))->listAction()->getContent());
        
        $this->assertEquals(0, count($list->jobs));      
    }
    
}


