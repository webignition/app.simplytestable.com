<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction;

class UserTest extends CountTest {      
    
    const JOB_TOTAL = 10;
    
    private $userEmailAddresses = array(
        'user1@example.com',
        'user2@example.com'        
    );
    
    private $users = array();
    private $counts = array();
    
    public function setUp() {
        parent::setUp();
        
        foreach ($this->userEmailAddresses as $emailAddress) {
            $this->users[] = $this->createAndActivateUser($emailAddress, 'password');
        }
        
        foreach ($this->getCanonicalUrls() as $index => $canonicalUrl) {    
            $user = ($index === 0) ? $this->users[0]->getEmail() : $this->users[1]->getEmail();
            $this->createJobAndGetId($canonicalUrl, $user);
        }    
        
        foreach ($this->users as $user) {
            $this->counts[$user->getEmail()] = json_decode($this->getJobListController('countAction', array(
                'user' => $user->getEmail()
            ))->countAction()->getContent()); 
        }
    }
    
    public function testCountZeroIsConstraintedToUserZero() {
        $this->assertEquals(1, $this->counts[$this->users[0]->getEmail()]);
    }
    
    public function testCountOneIsConstraintedToUserOne() {
        $this->assertEquals(9, $this->counts[$this->users[1]->getEmail()]);
    }
    
    
    protected function getCanonicalUrls() {
        return $this->getCanonicalUrlCollection(self::JOB_TOTAL);
    }

}


