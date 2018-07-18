<?php

namespace App\Tests\Functional\Controller\JobConfiguration;

use App\Entity\Job\Configuration;
use App\Entity\User;
use App\Tests\Factory\JobConfigurationFactory;
use App\Tests\Factory\UserFactory;
use App\Entity\Job\Configuration as JobConfiguration;

/**
 * @group Controller/JobConfiguration
 */
class JobConfigurationControllerDeleteActionTest extends AbstractJobConfigurationControllerTest
{
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

        $userFactory = new UserFactory(self::$container);
        $this->user = $userFactory->createAndActivateUser();
        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);
        $this->jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);
    }

    public function testDeleteActionGetRequest()
    {
        $router = self::$container->get('router');
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
        $router = self::$container->get('router');
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

    public function testDeleteActionSuccess()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobConfigurationRepository = $entityManager->getRepository(Configuration::class);

        $response = $this->jobConfigurationController->deleteAction(
            self::$container->get('doctrine.orm.entity_manager'),
            $this->jobConfiguration->getLabel()
        );

        $this->assertTrue($response->isSuccessful());

        $jobConfiguration = $jobConfigurationRepository->findOneBy([
            'label' => $this->jobConfiguration->getLabel(),
        ]);

        $this->assertNull($jobConfiguration);
    }
}
