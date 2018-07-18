<?php

namespace Tests\AppBundle\Functional\Services;

use AppBundle\Model\ApplicationStatus;
use AppBundle\Services\ApplicationStatusFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;

class ApplicationStatusFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var ApplicationStatusFactory
     */
    private $applicationStatusFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->applicationStatusFactory = self::$container->get(ApplicationStatusFactory::class);
    }

    public function testCreate()
    {
        $applicationStatus = $this->applicationStatusFactory->create();

        $this->assertInstanceOf(ApplicationStatus::class, $applicationStatus);

        $applicationStatusData = $applicationStatus->jsonSerialize();


        $this->assertInternalType('string', $applicationStatusData['state']);
        $this->assertInternalType('array', $applicationStatusData['workers']);
        $this->assertInternalType('string', $applicationStatusData['version']);
        $this->assertInternalType('int', $applicationStatusData['task_throughput_per_minute']);
        $this->assertInternalType('int', $applicationStatusData['in_progress_job_count']);

        $this->assertRegExp('/[a-f0-9]{40}/', $applicationStatusData['version']);
    }
}
