<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction;

class UserTest extends ListTest {      
    
    const JOB_TOTAL = 10;
    
    private $userEmailAddresses = array(
        'user1@example.com',
        'user2@example.com'        
    );
    
    private $users = array();
    private $lists = array();
    private $jobIds = array();
    
    public function setUp() {
        parent::setUp();
        
        foreach ($this->userEmailAddresses as $emailAddress) {
            $this->users[] = $this->createAndActivateUser($emailAddress, 'password');
            $this->jobIds[$emailAddress] = array();
        }
        
        foreach ($this->getCanonicalUrlCollection(10) as $index => $canonicalUrl) {                                    
            $this->jobIds[$this->users[$index % 2]->getEmail()][] = $this->createJobAndGetId($canonicalUrl, $this->users[$index % 2]->getEmail());
        }    
        
        foreach ($this->users as $user) {
            $this->lists[$user->getEmail()] = json_decode($this->getJobListController('listAction', array(
                'user' => $user->getEmail()
            ))->listAction(count($this->jobIds[$user->getEmail()]))->getContent()); 
        }       
    }
    
    public function testListZeroIsConstraintedToUserZero() {
        $list = $this->lists[$this->users[0]->getEmail()];
        foreach ($list->jobs as $job) {
            $this->assertEquals($this->users[0]->getEmail(), $job->user);
        }
    }
    
    public function testListOneIsConstraintedToUserOne() {
        $list = $this->lists[$this->users[1]->getEmail()];
        foreach ($list->jobs as $job) {
            $this->assertEquals($this->users[1]->getEmail(), $job->user);
        }
    }
    
    
    protected function getCanonicalUrls() {
        return $this->getCanonicalUrlCollection(self::JOB_TOTAL);
    }

}


