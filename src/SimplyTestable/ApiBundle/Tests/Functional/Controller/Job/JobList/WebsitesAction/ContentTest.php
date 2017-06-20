<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction;

abstract class ContentTest extends SingleUserTest {

    abstract protected function getExpectedWebsitesList();

    public function testWebsiteList() {
        $this->assertEquals($this->getExpectedWebsitesList(), $this->list);
    }
}