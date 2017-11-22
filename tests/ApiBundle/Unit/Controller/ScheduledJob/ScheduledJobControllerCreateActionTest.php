<?php

namespace Tests\ApiBundle\Unit\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Tests\ApiBundle\Factory\MockFactory;
use Tests\ApiBundle\Factory\ModelFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @group Controller/ScheduledJob
 */
class ScheduledJobControllerCreateActionTest extends AbstractScheduledJobControllerTest
{
    public function testCreateActionInMaintenanceReadOnlyMode()
    {
        $scheduledJobController = $this->createScheduledJobController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $scheduledJobController->createAction(
            MockFactory::createUserService(),
            MockFactory::createJobConfigurationService(),
            MockFactory::createCronModifierValidationService(),
            ModelFactory::createUser(),
            new Request()
        );
    }

    /**
     * @dataProvider createActionBadRequestDataProvider
     *
     * @param string $jobConfiguration
     * @param string $schedule
     * @param string $expectedExceptionMessage
     */
    public function testCreateActionBadRequest($jobConfiguration, $schedule, $expectedExceptionMessage)
    {
        $scheduledJobController = $this->createScheduledJobController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(),
        ]);

        $request = new Request([], [
            'job-configuration' => $jobConfiguration,
            'schedule' => $schedule,
        ]);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $scheduledJobController->createAction(
            MockFactory::createUserService(),
            MockFactory::createJobConfigurationService(),
            MockFactory::createCronModifierValidationService(),
            ModelFactory::createUser(),
            $request
        );
    }

    /**
     * @return array
     */
    public function createActionBadRequestDataProvider()
    {
        return [
            'job-configuration missing' => [
                'jobConfiguration' => null,
                'schedule' => null,
                'expectedExceptionMessage' => '"job-configuration" missing',
            ],
            'schedule missing' => [
                'jobConfiguration' => 'job configuration label',
                'schedule' => null,
                'expectedExceptionMessage' => '"schedule" missing',
            ],
        ];
    }

    public function testCreateActionSpecialUser()
    {
        $scheduledJobController = $this->createScheduledJobController();

        $user = MockFactory::createUser();

        $userService = MockFactory::createUserService([
            'isSpecialUser' => [
                'with' => $user,
                'return' => true,
            ],
        ]);

        $request = new Request([], [
            'job-configuration' => 'job configuration label',
            'schedule' => '* * * * *',
        ]);

        $response = $scheduledJobController->createAction(
            $userService,
            MockFactory::createJobConfigurationService(),
            MockFactory::createCronModifierValidationService(),
            $user,
            $request
        );

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":99,"message":"Special users cannot create scheduled jobs"}',
            $response->headers->get('x-scheduledjobcreate-error')
        );
    }
}
