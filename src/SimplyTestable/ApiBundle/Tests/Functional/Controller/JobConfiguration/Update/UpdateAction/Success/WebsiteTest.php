<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction\Success;

class WebsiteTest extends SuccessTest {

    public function setUp() {
        parent::setUp();
    }

    protected function getNewLabel() {
        return '';
    }

    protected function getNewWebsite() {
        return 'http://foo.example.com/';
    }

    protected function getNewJobType() {
        return $this->getOriginalJobType();
    }

    protected function getNewTaskConfiguration() {
        return $this->originalTaskConfiguration;
    }

    protected function getNewParameters() {
        return $this->getOriginalParameters();
    }
}