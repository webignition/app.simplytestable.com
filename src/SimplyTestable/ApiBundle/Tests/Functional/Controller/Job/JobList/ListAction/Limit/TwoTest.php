<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\Limit;

class TwoTest extends LimitTest {

    protected function getLimit() {
        return 2;
    }

    protected function getExpectedListLength() {
        return 2;
    }

}