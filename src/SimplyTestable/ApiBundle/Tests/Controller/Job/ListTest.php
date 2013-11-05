<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class ListTest extends BaseControllerJsonTestCase {      
    
    public function setUp() {        
        self::setupDatabase();
        parent::setUp();        
    }     
    
    public function testListAction() {
        $jobController = $this->getJobController('listAction');        
        $jobStartController = $this->getJobStartController('startAction');
        
        $canonicalUrls = array(
            'http://one.example.com/',
            'http://two.example.com/',
            'http://three.example.com/',
            'http://four.example.com/',
            'http://five.example.com/'
        );
        
        foreach ($canonicalUrls as $canonicalUrl) {
            $jobStartController->startAction($canonicalUrl);            
        }       
        
        // Test with no limit set (should default to a limit of 1)
        $listResponse = $jobController->listAction();
        
        $this->assertEquals(200, $listResponse->getStatusCode());
        
        $listResponseObject = json_decode($listResponse->getContent());
        
        $this->assertTrue(is_array($listResponseObject));
        $this->assertEquals(5, $listResponseObject[0]->id);
        $this->assertEquals('http://five.example.com/', $listResponseObject[0]->website);
        
        // Test with limits of 1 to 5 (number of jobs in the system)
        for ($limit = 1; $limit <= count($canonicalUrls); $limit++) {            
            $listResponse = $jobController->listAction($limit);
            $this->assertEquals(200, $listResponse->getStatusCode());
            $listResponseObject = json_decode($listResponse->getContent());
            
            $this->assertTrue(is_array($listResponseObject));
            $this->assertEquals($limit, count($listResponseObject));
            
            $expectedJobIds = $this->getExpectedJobIdsForLimit($limit, count($canonicalUrls));
            foreach ($expectedJobIds as $jobIdIndex => $expectedJobId) {
                $this->assertTrue($this->jobListContainsJobId($listResponseObject, $expectedJobId));
                $this->assertEquals($canonicalUrls[$expectedJobId - 1], $listResponseObject[$jobIdIndex]->website);
            }           
        }     
    }  
    
    
    public function testListActionForDifferentUsers() {        
        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');
        
        $canonicalUrls = array(
            array(
                'http://one.example.com/',
                'http://two.example.com/',
                'http://three.example.com/',
                'http://four.example.com/',
                'http://five.example.com/'                
            ),
            array(
                'http://user1.one.example.com/',
                'http://user1.two.example.com/',
                'http://user1.three.example.com/'              
            ),
            array(
                'http://user2.one.example.com/',
                'http://user2.two.example.com/',
                'http://user2.three.example.com/',
                'http://user2.four.example.com/'              
            )            
        );
        
        foreach ($canonicalUrls as $urlSetIndex => $canonicalUrlSet) {            
            switch ($urlSetIndex) {
                case 0:
                    $postData = array();
                    break;
                
                case 1:
                    $postData = array(
                        'user' => $user1->getEmail()
                    );
                    break;
                
                case 2:
                    $postData = array(
                        'user' => $user2->getEmail()
                    );
                    break;                
            }
            
            foreach ($canonicalUrlSet as $canonicalUrl) {
                $this->getJobStartController('startAction', $postData)->startAction($canonicalUrl);
            }
        }        

        // Test with no limit set (should default to a limit of 1)        
        $list1Response = $this->getJobController('listAction', array())->listAction();        
        $list1ResponseObject = json_decode($list1Response->getContent());
        
        $this->assertEquals(200, $list1Response->getStatusCode());
        $this->assertTrue(is_array($list1ResponseObject));
        $this->assertEquals(count($canonicalUrls[0]), $list1ResponseObject[0]->id);
        $this->assertEquals('http://five.example.com/', $list1ResponseObject[0]->website);        
        
        $list2Response = $this->getJobController('listAction', array(
            'user' => $user1->getEmail()
        ))->listAction();        
        $list2ResponseObject = json_decode($list2Response->getContent());
        
        $this->assertEquals(200, $list2Response->getStatusCode());
        $this->assertTrue(is_array($list2ResponseObject));
        $this->assertEquals(count($canonicalUrls[0]) + count($canonicalUrls[1]), $list2ResponseObject[0]->id);
        $this->assertEquals('http://user1.three.example.com/', $list2ResponseObject[0]->website);        
        
        $list3Response = $this->getJobController('listAction', array(
            'user' => $user2->getEmail()
        ))->listAction();        
        $list3ResponseObject = json_decode($list3Response->getContent());
        
        $this->assertEquals(200, $list3Response->getStatusCode());
        $this->assertTrue(is_array($list3ResponseObject));
        $this->assertEquals(count($canonicalUrls[0]) + count($canonicalUrls[1]) + count($canonicalUrls[2]), $list3ResponseObject[0]->id);
        $this->assertEquals('http://user2.four.example.com/', $list3ResponseObject[0]->website);  
        
        // Test with limits of 1 to X (number of jobs in the system for the given user)
        foreach ($canonicalUrls as $urlSetIndex => $canonicalUrlSet) {
            switch ($urlSetIndex) {
                case 0:
                    $postData = array();
                    $offset = 0;
                    break;
                
                case 1:
                    $postData = array(
                        'user' => $user1->getEmail()
                    );
                    
                    $offset = count($canonicalUrls[0]);
                    break;
                
                case 2:
                    $postData = array(
                        'user' => $user2->getEmail()
                    );
                    $offset = count($canonicalUrls[0]) + count($canonicalUrls[1]);
                    break;                
            }
            
            for ($limit = 1; $limit <= count($canonicalUrlSet); $limit++) {            
                $listResponse = $this->getJobController('listAction', $postData)->listAction($limit);
                $this->assertEquals(200, $listResponse->getStatusCode());
                $listResponseObject = json_decode($listResponse->getContent());

                $this->assertTrue(is_array($listResponseObject));
                $this->assertEquals($limit, count($listResponseObject));

                $expectedJobIds = $this->getExpectedJobIdsForLimit($limit, count($canonicalUrlSet), $offset);                
                
                foreach ($expectedJobIds as $jobIdIndex => $expectedJobId) {
                    $this->assertTrue($this->jobListContainsJobId($listResponseObject, $expectedJobId));
                    $this->assertEquals($canonicalUrlSet[$expectedJobId - 1 - $offset], $listResponseObject[$jobIdIndex]->website);
                }           
            }             
        }        
    }
    
    public function testExludeCrawlJobsFromList() {
        $this->createJobAndGetId('http://one.example.com');
        $job2_id = $this->createJobAndGetId('http://two.example.com');
        $this->createJobAndGetId('http://three.example.com');
        
        $job2 = $this->getJobService()->getById($job2_id);
        $job2->setType($this->getJobTypeService()->getByName('crawl'));
        $this->getJobService()->getEntityManager()->persist($job2);
        $this->getJobService()->getEntityManager()->flush();         
        
        $listResponse = $this->getJobController('listAction', array(), array('exclude-types' => array('crawl', 'foo')))->listAction(3);      
        
        $this->assertEquals(2, count(json_decode($listResponse->getContent())));
    }

    public function testExcludeCurrentJobsFromList() {
        $jobs = array();
        $jobs[] = $this->getJobService()->getById($this->createJobAndGetId('http://one.example.com', null, 'single url'));
        $jobs[] = $this->getJobService()->getById($this->createJobAndGetId('http://two.example.com', null, 'single url'));
        $jobs[] = $this->getJobService()->getById($this->createJobAndGetId('http://three.example.com', null, 'single url'));
        
        $jobs[0]->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($jobs[0]);

        $listObject = json_decode($this->getJobController('listAction', array(), array(
            'exclude-current' => '1'
        ))->listAction(count($jobs))->getContent());
        
        $this->assertEquals(1, count($listObject));
        $this->assertEquals($jobs[0]->getId(), $listObject[0]->id);
    } 
    
    
    public function testExcludeByStatesFromList() {
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

    /**
     * 
     * @param int $limit
     * @param int $jobCount
     * @return array
     */
    private function getExpectedJobIdsForLimit($limit, $jobCount, $offset = 0) {        
        $jobIds = array();
        
        while ($limit != count($jobIds)) {
            $jobIds[] = $jobCount - count($jobIds) + $offset;
        }
        
        return $jobIds;
    }
    
    
    /**
     * 
     * @param array $jobList
     * @param int $id
     * @return boolean
     */
    private function jobListContainsJobId($jobList, $id) {        
        foreach ($jobList as $job) {
            if ($job->id == $id) {
                return true;
            }
        }
        
        return false;
    }     
    
}


