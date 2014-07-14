<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Retrieval;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class ServiceTest extends BaseSimplyTestableTestCase {

    /**
     * @return \SimplyTestable\ApiBundle\Services\Job\RetrievalService
     */
    protected function getJobRetrievalService() {
        return $this->container->get('simplytestable.services.job.retrievalservice');
    }

}
