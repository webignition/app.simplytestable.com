<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\ResolveWebsiteCommand;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class ExceptionCasesTest extends CommandTest
{
    public function testJobInWrongStateReturnsStatusCode1()
    {
        $job = $this->createJobFactory()->create();
        $job->setState($this->getJobService()->getCancelledState());
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
    }

    public function testHttpClientErrorPerformingResolution()
    {
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404'
        )));

        $job = $this->createJobFactory()->create([
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
