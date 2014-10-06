<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction;

class UserTest extends WebsitesTest {      
    
    const JOB_TOTAL = 10;
    
    private $userEmailAddresses = array(
        'user1@example.com',
        'user2@example.com'        
    );
    
    private $users = array();
    private $lists = array();
    
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
            $this->getUserService()->setUser($user);

            $this->lists[$user->getEmail()] = json_decode($this->getJobListController('WebsitesAction')->WebsitesAction()->getContent());
        }
    }

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }
    
    public function testListZeroIsConstraintedToUserZero() {
        $this->assertEquals(array_slice($this->getCanonicalUrls(), 0, 1), $this->lists[$this->users[0]->getEmail()]);
    }
    
    public function testListOneIsConstraintedToUserOne() {
        $expectedUrlSet = array_slice($this->getCanonicalUrls(), 1);
        sort($expectedUrlSet);
        
        $this->assertEquals($expectedUrlSet, $this->lists[$this->users[1]->getEmail()]);
    }
    
    
    protected function getCanonicalUrls() {
        return $this->getCanonicalUrlCollection(self::JOB_TOTAL);
    }

}


