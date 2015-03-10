<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\Success;

class TypeTest extends SuccessTest {

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
        return $this->getJobTypeService()->getSingleUrlType();
    }

    protected function getNewTaskConfiguration() {
        return $this->originalTaskConfiguration;
    }

    protected function getNewParameters() {
        return $this->getOriginalParameters();
    }
}