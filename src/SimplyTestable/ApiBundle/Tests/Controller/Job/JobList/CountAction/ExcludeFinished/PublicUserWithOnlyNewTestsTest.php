<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\ExcludeFinished;

class PublicUserWithOnlyNewTestsTest extends ExcludeFinishedTest {
    
    private $canonicalUrls = array(
        'http://one.example.com/',
        'http://two.example.com/',
        'http://three.example.com/'          
    );

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }

    protected function getExpectedCountValue() {
        return 3;
    }

    protected function getCanonicalUrls() {
        return $this->canonicalUrls;
    }   
}


