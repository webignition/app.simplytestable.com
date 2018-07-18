<?php

namespace Tests\AppBundle\Unit\Controller\JobConfiguration;

use AppBundle\Services\ApplicationStateService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tests\AppBundle\Factory\MockFactory;
use Tests\AppBundle\Factory\ModelFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @group Controller/JobConfiguration
 */
class JobConfigurationControllerCreateActionTest extends AbstractJobConfigurationControllerTest
{
    public function testCreateActionInMaintenanceReadOnlyMode()
    {
        $jobConfigurationController = $this->createJobConfigurationController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $jobConfigurationController->createAction(
            MockFactory::createUserService(),
            MockFactory::createWebSiteService(),
            MockFactory::createTaskTypeService(),
            MockFactory::createJobTypeService(),
            ModelFactory::createUser(),
            new Request()
        );
    }

    /**
     * @dataProvider createActionBadRequestDataProvider
     *
     * @param string $label
     * @param string $website
     * @param string $type
     * @param array $taskConfiguration
     * @param string $expectedExceptionMessage
     */
    public function testCreateActionBadRequest($label, $website, $type, $taskConfiguration, $expectedExceptionMessage)
    {
        $jobConfigurationController = $this->createJobConfigurationController();

        $request = new Request([], [
            'label' => $label,
            'website' => $website,
            'type' => $type,
            'task-configuration' => $taskConfiguration,
        ]);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $jobConfigurationController->createAction(
            MockFactory::createUserService(),
            MockFactory::createWebSiteService(),
            MockFactory::createTaskTypeService(),
            MockFactory::createJobTypeService(),
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
            'label missing' => [
                'label' => null,
                'website' => null,
                'type' => null,
                'taskConfiguration' => null,
                'expectedExceptionMessage' => '"label" missing',
            ],
            'website missing' => [
                'label' => 'label value',
                'website' => null,
                'type' => null,
                'taskConfiguration' => null,
                'expectedExceptionMessage' => '"website" missing',
            ],
            'type missing' => [
                'label' => 'label value',
                'website' => 'http://example.com',
                'type' => null,
                'taskConfiguration' => null,
                'expectedExceptionMessage' => '"type" missing',
            ],
            'task-configuration missing' => [
                'label' => 'label value',
                'website' => 'http://example.com',
                'type' => 'full site',
                'taskConfiguration' => null,
                'expectedExceptionMessage' => '"task-configuration" missing',
            ],
        ];
    }

    public function testCreateActionSpecialUser()
    {
        $jobConfigurationController = $this->createJobConfigurationController();

        $user = MockFactory::createUser();

        $userService = MockFactory::createUserService([
            'isSpecialUser' => [
                'with' => $user,
                'return' => true,
            ],
        ]);

        $request = new Request([], [
            'label' => 'label value',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        $response = $jobConfigurationController->createAction(
            $userService,
            MockFactory::createWebSiteService(),
            MockFactory::createTaskTypeService(),
            MockFactory::createJobTypeService(),
            $user,
            $request
        );

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":99,"message":"Special users cannot create job configurations"}',
            $response->headers->get('x-jobconfigurationcreate-error')
        );
    }
}
