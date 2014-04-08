<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\Limit;

class ZeroTest extends LimitOneTest {
    
    protected function getLimit() {
        return 0;
    }
    
}