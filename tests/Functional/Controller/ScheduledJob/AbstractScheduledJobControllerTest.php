<?php

namespace App\Tests\Functional\Controller\ScheduledJob;

use App\Controller\ScheduledJobController;
use App\Tests\Functional\Controller\AbstractControllerTest;

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
