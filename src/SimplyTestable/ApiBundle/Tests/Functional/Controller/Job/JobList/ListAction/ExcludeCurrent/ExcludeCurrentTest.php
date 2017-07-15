<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ExcludeCurrent;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ListContentTest;

abstract class ExcludeCurrentTest extends ListContentTest {

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }

    protected function getQueryParameters() {
        return array(
            'exclude-current' => '1'
        );
    }

}