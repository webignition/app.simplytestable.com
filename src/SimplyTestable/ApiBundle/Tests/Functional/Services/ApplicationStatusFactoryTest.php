<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Model\ApplicationStatus;
use SimplyTestable\ApiBundle\Services\ApplicationStatusFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ApplicationStatusFactoryTest extends BaseSimplyTestableTestCase
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

        $this->applicationStatusFactory = $this->container->get('simplytestable.services.applicationstatusfactory');
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
