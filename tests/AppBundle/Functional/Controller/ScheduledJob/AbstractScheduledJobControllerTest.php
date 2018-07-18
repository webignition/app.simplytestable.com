<?php

namespace Tests\AppBundle\Functional\Controller\ScheduledJob;

use AppBundle\Controller\ScheduledJobController;
use Tests\AppBundle\Functional\Controller\AbstractControllerTest;

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

        $this->scheduledJobController = self::$container->get(ScheduledJobController::class);
    }
}
