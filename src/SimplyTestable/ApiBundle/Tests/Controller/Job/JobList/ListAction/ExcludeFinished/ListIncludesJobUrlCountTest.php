<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeFinished;

class ListIncludesJobUrlCountTest extends ExcludeFinishedTest {

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }

    protected function getExpectedListLength() {
        return 1;
    }

    protected function getCanonicalUrls() {
        return array('http://one.example.com/');
    }

    protected function getExpectedJobListUrls() {
        return array('http://one.example.com/');
    }
    
    public function testListIncludesJobUrlCount() {        
        $this->assertTrue(isset($this->list->jobs[0]->url_count));   
    }      

}


