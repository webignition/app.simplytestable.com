<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class ExceptionCasesTest extends CommandTest
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);
    }


    public function testJobInWrongStateReturnsStatusCode1()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobCancelledState = $stateService->fetch(JobService::CANCELLED_STATE);

        $job = $this->jobFactory->create();
        $job->setState($jobCancelledState);
        $this->getJobService()->persistAndFlush($job);

        $this->assertReturnCode(1, array(
            'id' => $job->getId()
        ));
    }

    public function testSystemInMaintenanceModeReturnsStatusCode2()
    {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(2, array(
            'id' => 1
        ));
        $this->executeCommand('simplytestable:maintenance:disable-read-only');
    }

    public function testHttpClientErrorPerformingResolution()
    {
        $this->queueHttpFixtures([
            HttpFixtureFactory::createNotFoundResponse(),
            HttpFixtureFactory::createNotFoundResponse(),
        ]);

        $job = $this->jobFactory->create([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
            JobFactory::KEY_TEST_TYPES => ['CSS Validation'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'CSS validation' => [
                    'ignore-common-cdns' => 1,
                ],
            ],
        ]);

        $this->assertReturnCode(0, array(
            'id' => $job->getId()
        ));
    }
}
