<?php

namespace Tests\AppBundle\Unit\Controller\JobConfiguration;

use AppBundle\Services\Job\ConfigurationService;
use Tests\AppBundle\Factory\MockFactory;
use Tests\AppBundle\Factory\ModelFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group Controller/JobConfiguration
 */
class JobConfigurationControllerGetActionTest extends AbstractJobConfigurationControllerTest
{
    const LABEL = 'foo';

    public function testGetActionJobConfigurationNotFound()
    {
        $jobConfigurationController = $this->createJobConfigurationController([
            ConfigurationService::class => MockFactory::createJobConfigurationService([
                'get' => [
                    'with' => self::LABEL,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $jobConfigurationController->getAction(self::LABEL);
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
                'get' => [
                    'with' => self::LABEL,
                    'return' => $jobConfiguration,
                ],
            ]),
        ]);

        $response = $jobConfigurationController->getAction(self::LABEL);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals($response->headers->get('content-type'), 'application/json');

        $responseData = json_decode($response->getContent(), true);
        $this->assertInternalType('array', $responseData);
    }
}
