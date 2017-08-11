<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction\Success;

use SimplyTestable\ApiBundle\Services\JobTypeService;

class TypeTest extends SuccessTest {

    protected function setUp() {
        parent::setUp();
    }

    protected function getNewLabel() {
        return '';
    }

    protected function getNewWebsite() {
        return $this->getOriginalWebsite();
    }

    protected function getNewJobType() {
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        return $jobTypeService->getByName(JobTypeService::SINGLE_URL_NAME);
    }

    protected function getNewTaskConfiguration() {
        return $this->originalTaskConfiguration;
    }

    protected function getNewParameters() {
        return $this->getOriginalParameters();
    }
}