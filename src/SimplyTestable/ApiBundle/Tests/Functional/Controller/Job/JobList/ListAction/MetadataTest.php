<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction;

class MetadataTest extends SingleListTest {

    const JOB_TOTAL = 10;

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }


    public function testMaxResultsIsSet() {
        $this->assertEquals(self::JOB_TOTAL, $this->list->max_results);
    }

    public function testOffsetIsSet() {
        $this->assertEquals(0, $this->list->offset);
    }

    protected function getCanonicalUrls() {
        return $this->getCanonicalUrlCollection(self::JOB_TOTAL);
    }

    protected function getQueryParameters() {
        return array();
    }

}