<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction\Success;

class ParametersTest extends SuccessTest {

    public function setUp() {
        parent::setUp();
    }

    protected function getNewLabel() {
        return '';
    }

    protected function getNewWebsite() {
        return $this->getOriginalWebsite();
    }

    protected function getNewJobType() {
        return $this->getOriginalJobType();
    }

    protected function getNewTaskConfiguration() {
        return $this->originalTaskConfiguration;
    }

    protected function getNewParameters() {
        return $this->getOriginalParameters() . '-foo';
    }
}