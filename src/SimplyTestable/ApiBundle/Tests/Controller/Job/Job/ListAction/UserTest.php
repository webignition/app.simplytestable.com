<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\ListAction;

class UserTest extends AbstractListTest {      
    
    public function testListIsConstrainedToCurrentUser() {
        $userEmailAddresses = array(
            'user1@example.com',
            'user2@example.com'
        );
        
        $users = array();
        $jobIds = array();        
        
        foreach ($userEmailAddresses as $emailAddress) {
            $users[] = $this->createAndActivateUser($emailAddress, 'password');
            $jobIds[$emailAddress] = array();
        }
        
        foreach ($this->getCanonicalUrlCollection(10) as $index => $canonicalUrl) {                                    
            $jobIds[$users[$index % 2]->getEmail()][] = $this->createJobAndGetId($canonicalUrl, $users[$index % 2]->getEmail());
        }
        
        $lists = array();
        foreach ($users as $user) {
            $lists[$user->getEmail()] = json_decode($this->getJobController('listAction', array(
                'user' => $user->getEmail()
            ))->listAction(count($jobIds[$user->getEmail()]))->getContent()); 
        }
        
        foreach ($lists as $user => $list) {
            $listJobIds = array();
            
            foreach ($list->jobs as $job) {
                $listJobIds[] = $job->id;
            }
            
            $this->assertEquals($jobIds[$user], array_reverse($listJobIds));
        }
    }    
    
}


