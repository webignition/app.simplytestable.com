<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\Limit;

class OneTest extends LimitOneTest {
    
    protected function getLimit() {
        return 1;
    }
    
}