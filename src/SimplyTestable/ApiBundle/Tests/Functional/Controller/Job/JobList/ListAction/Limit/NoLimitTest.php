<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\Limit;

class NoLimitTest extends LimitOneTest {

    protected function getLimit() {
        return null;
    }

}