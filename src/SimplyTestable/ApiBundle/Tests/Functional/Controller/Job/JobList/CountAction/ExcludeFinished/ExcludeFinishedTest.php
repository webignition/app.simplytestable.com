<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\ExcludeFinished;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\ContentTest;

abstract class ExcludeFinishedTest extends ContentTest {

    protected function getQueryParameters() {
        return array(
            'exclude-finished' => '1'
        );
    }

}


