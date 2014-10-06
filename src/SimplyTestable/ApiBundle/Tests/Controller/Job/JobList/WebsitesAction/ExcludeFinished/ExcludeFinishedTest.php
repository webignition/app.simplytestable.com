<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\ExcludeFinished;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\ContentTest;

abstract class ExcludeFinishedTest extends ContentTest {

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }
    
    protected function getQueryParameters() {
        return array(
            'exclude-finished' => '1'
        );
    }
    
}


