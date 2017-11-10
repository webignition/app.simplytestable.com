<?php

namespace Tests\ApiBundle\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfigurationController;
use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Tests\ApiBundle\Factory\JobConfigurationFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class JobConfigurationControllerDeleteActionTest extends AbstractBaseTestCase
{
    /**
     * @var JobConfigurationController
     */
    private $jobConfigurationController;

    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;

    /**
     * @var User
     */
    private $user;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobConfigurationController = new JobConfigurationController();
        $this->jobConfigurationController->setContainer($this->container);

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->createAndActivateUser();
        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);
    }

    public function testDeleteActionGetRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_delete', [
            'label' => $this->jobConfiguration->getLabel(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(405, $response->getStatusCode());
    }


    public function testDeleteActionPostRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_delete', [
            'label' => $this->jobConfiguration->getLabel(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testDeleteActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        try {
            $this->jobConfigurationController->deleteAction('foo');
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
    }

    public function testDeleteActionJobConfigurationNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->jobConfigurationController->deleteAction('foo');
    }

    public function testDeleteActionJobConfigurationBelongsToScheduledJob()
    {
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $scheduledJobService->create(
            $this->jobConfiguration
        );

        $response = $this->jobConfigurationController->deleteAction($this->jobConfiguration->getLabel());

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            '{"code":1,"message":"Job configuration is in use by a scheduled job"}',
            $response->headers->get('X-JobConfigurationDelete-Error')
        );
    }

    public function testDeleteActionSuccess()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobConfigurationRepository = $entityManager->getRepository(Configuration::class);

        $response = $this->jobConfigurationController->deleteAction($this->jobConfiguration->getLabel());

        $this->assertTrue($response->isSuccessful());

        $jobConfiguration = $jobConfigurationRepository->findOneBy([
            'label' => $this->jobConfiguration->getLabel(),
        ]);

        $this->assertNull($jobConfiguration);
    }
}
