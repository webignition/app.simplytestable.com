<?php

namespace App\Tests\Functional\Controller\JobConfiguration;

use App\Controller\JobConfigurationController;
use App\Tests\Functional\Controller\AbstractControllerTest;

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
