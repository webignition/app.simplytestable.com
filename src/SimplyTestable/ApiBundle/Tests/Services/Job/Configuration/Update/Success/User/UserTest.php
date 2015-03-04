<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\Success\User;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\Success\SuccessTest;

class UserTest extends SuccessTest {

    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }

    protected function getOriginalWebsite() {
        return $this->getWebSiteService()->fetch('http://original.example.com/');
    }

    protected function getOriginalJobType() {
        return $this->getJobTypeService()->getFullSiteType();
    }

    protected function getOriginalParameters() {
        return 'original-parameters';
    }

    protected function getNewWebsite() {
        return $this->getWebSiteService()->fetch('http://new.example.com/');
    }

    protected function getNewJobType() {
        return $this->getJobTypeService()->getSingleUrlType();
    }

    protected function getNewParameters() {
        return 'new-parameters';
    }
}