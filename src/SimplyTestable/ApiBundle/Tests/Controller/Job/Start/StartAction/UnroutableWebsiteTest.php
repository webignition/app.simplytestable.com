<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start\StartAction;

class UnroutableWebsiteTest extends RedirectResponseTest {

    protected function getCanonicalUrl() {
        return 'http://foo';
    }
}