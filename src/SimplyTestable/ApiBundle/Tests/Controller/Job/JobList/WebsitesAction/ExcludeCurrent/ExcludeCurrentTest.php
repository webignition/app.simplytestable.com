<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\ExcludeCurrent;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\ContentTest;

abstract class ExcludeCurrentTest extends ContentTest {

    protected function getQueryParameters() {
        return array(
            'exclude-current' => '1'
        );
    }

}