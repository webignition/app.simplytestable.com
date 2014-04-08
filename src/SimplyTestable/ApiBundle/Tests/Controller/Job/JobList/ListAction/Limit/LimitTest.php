<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\Limit;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ListContentTest;

abstract class LimitTest extends ListContentTest {       
    
    const JOB_LIMIT = 10;
    
    protected function getCanonicalUrls() {
        return $this->getCanonicalUrlCollection(self::JOB_LIMIT);
    }

    protected function getExpectedJobListUrls() {
        return array_slice(array_reverse($this->getCanonicalUrls()), 0, $this->getExpectedListLength());
    }

    protected function getQueryParameters() {
        return array();
    }

}


