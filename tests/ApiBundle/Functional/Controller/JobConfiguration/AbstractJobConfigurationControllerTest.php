<?php

namespace Tests\ApiBundle\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfigurationController;
use Tests\ApiBundle\Functional\Controller\AbstractControllerTest;

abstract class AbstractJobConfigurationControllerTest extends AbstractControllerTest
{
    /**
     * @var JobConfigurationController
     */
    protected $jobConfigurationController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobConfigurationController = $this->container->get(JobConfigurationController::class);
    }
}
