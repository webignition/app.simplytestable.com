<?php

namespace Tests\AppBundle\Functional\Controller\JobConfiguration;

use AppBundle\Controller\JobConfigurationController;
use Tests\AppBundle\Functional\Controller\AbstractControllerTest;

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

        $this->jobConfigurationController = self::$container->get(JobConfigurationController::class);
    }
}
