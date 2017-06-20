<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\ExcludeCurrent;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\ContentTest;

abstract class ExcludeCurrentTest extends ContentTest {

    protected function getQueryParameters() {
        return array(
            'exclude-current' => '1'
        );
    }

}