<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\Limit;

class NegativeTest extends LimitOneTest {

    protected function getLimit() {
        return -1;
    }
}