<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\Limit;

abstract class LimitOneTest extends LimitTest {
    
    protected function getExpectedListLength() {
        return 1;
    }
    
}