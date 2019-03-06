<?php

namespace App\Tests\Functional\Controller\JobConfiguration;

use App\Entity\Job\Configuration;
use App\Entity\User;
use App\Repository\ScheduledJobRepository;
use App\Tests\Services\UserFactory;
use App\Entity\Job\Configuration as JobConfiguration;
use App\Tests\Services\JobConfigurationFactory;
use Doctrine\ORM\EntityManagerInterface;

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

        $jobConfigurationFactory = self::$container->get(JobConfigurationFactory::class);

        $userFactory = self::$container->get(UserFactory::class);
        $this->user = $userFactory->createAndActivateUser();
        $this->setUser($this->user);

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
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $jobConfigurationRepository = $entityManager->getRepository(Configuration::class);
        $scheduledJobRepository = self::$container->get(ScheduledJobRepository::class);

        $response = $this->jobConfigurationController->deleteAction(
            $scheduledJobRepository,
            $this->jobConfiguration->getLabel()
        );

        $this->assertTrue($response->isSuccessful());

        $jobConfiguration = $jobConfigurationRepository->findOneBy([
            'label' => $this->jobConfiguration->getLabel(),
        ]);

        $this->assertNull($jobConfiguration);
    }
}
