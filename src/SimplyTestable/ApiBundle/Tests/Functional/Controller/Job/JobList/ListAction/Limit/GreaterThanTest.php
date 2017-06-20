<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\Limit;

class GreaterThanTest extends LimitTest {

    protected function getLimit() {
        return $this->getExpectedListLength() * 2;
    }

    protected function getExpectedListLength() {
        return count($this->getCanonicalUrls());
    }

}