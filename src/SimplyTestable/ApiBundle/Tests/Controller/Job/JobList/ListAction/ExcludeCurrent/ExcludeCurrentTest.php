<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeCurrent;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ListContentTest;

abstract class ExcludeCurrentTest extends ListContentTest {

    protected function getQueryParameters() {
        return array(
            'exclude-current' => '1'
        );
    }

}