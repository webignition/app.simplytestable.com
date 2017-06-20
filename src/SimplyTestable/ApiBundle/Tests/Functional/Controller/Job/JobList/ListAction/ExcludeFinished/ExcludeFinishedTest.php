<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ExcludeFinished;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ListContentTest;

abstract class ExcludeFinishedTest extends ListContentTest {

    protected function getQueryParameters() {
        return array(
            'exclude-finished' => '1'
        );
    }

}


