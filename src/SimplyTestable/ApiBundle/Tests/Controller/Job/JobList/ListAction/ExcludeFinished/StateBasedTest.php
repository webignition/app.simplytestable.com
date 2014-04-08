<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeFinished;

abstract class StateBasedTest extends ExcludeFinishedTest {

    protected function getStateBasedCanonicalUrls($states) {
        $canonicalUrls = array();
        
        foreach ($states as $state) {
            $canonicalUrls[] = 'http://' . $state->getName() . '.example.com/';
        }
        
        return $canonicalUrls;
    }

}