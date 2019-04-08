<?php

namespace App\Tests\Unit\Controller\JobConfiguration;

use App\Services\Job\ConfigurationService;
use App\Tests\Factory\MockFactory;
use App\Tests\Factory\ModelFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group Controller/JobConfiguration
 */
class JobConfigurationControllerGetActionTest extends AbstractJobConfigurationControllerTest
{
    const JOB_CONFIGURATION_ID = 1;

    public function testGetActionJobConfigurationNotFound()
    {
        $jobConfigurationController = $this->createJobConfigurationController([
            ConfigurationService::class => MockFactory::createJobConfigurationService([
                'getById' => [
                    'with' => self::JOB_CONFIGURATION_ID,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $jobConfigurationController->getAction(self::JOB_CONFIGURATION_ID);
    }

    public function testGetActionSuccess()
    {
        $jobConfiguration = ModelFactory::createJobConfiguration([
            ModelFactory::JOB_CONFIGURATION_LABEL => 'foo',
            ModelFactory::JOB_CONFIGURATION_USER => ModelFactory::createUser([
                ModelFactory::USER_EMAIL => 'user@example.com',
            ]),
            ModelFactory::JOB_CONFIGURATION_WEBSITE => ModelFactory::createWebsite([
                ModelFactory::WEBSITE_CANONICAL_URL => 'http://foo.example.com/',
            ]),
            ModelFactory::JOB_CONFIGURATION_TYPE => ModelFactory::createJobType([
                ModelFactory::JOB_TYPE_NAME => 'job type name',
            ]),
            ModelFactory::JOB_CONFIGURATION_PARAMETERS => 'parameters string'
        ]);

        $jobConfigurationController = $this->createJobConfigurationController([
            ConfigurationService::class => MockFactory::createJobConfigurationService([
                'getById' => [
                    'with' => self::JOB_CONFIGURATION_ID,
                    'return' => $jobConfiguration,
                ],
            ]),
        ]);

        $response = $jobConfigurationController->getAction(self::JOB_CONFIGURATION_ID);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals($response->headers->get('content-type'), 'application/json');

        $responseData = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $responseData);
    }
}
