<?php

namespace Tests\ApiBundle\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJobController;
use Tests\ApiBundle\Functional\Controller\AbstractControllerTest;

abstract class AbstractScheduledJobControllerTest extends AbstractControllerTest
{
    /**
     * @var ScheduledJobController
     */
    protected $scheduledJobController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->scheduledJobController = $this->container->get(ScheduledJobController::class);
    }
}
