<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeFinished;

class PublicUserWithOnlyNewTestsTest extends ExcludeFinishedTest {

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }
    
    private $canonicalUrls = array(
        'http://one.example.com/',
        'http://two.example.com/',
        'http://three.example.com/'          
    );

    protected function getExpectedListLength() {
        return 3;
    }

    protected function getCanonicalUrls() {
        return $this->canonicalUrls;
    }    
    
    protected function getExpectedJobListUrls() {
        return array_reverse($this->canonicalUrls);
    }    
}


