<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\ExcludeFinished;

abstract class StateBasedTest extends ExcludeFinishedTest {

    protected function getStateBasedCanonicalUrls($states) {
        $canonicalUrls = array();

        foreach ($states as $state) {
            $canonicalUrls[] = 'http://' . $state->getName() . '.example.com/';
        }

        return $canonicalUrls;
    }

}