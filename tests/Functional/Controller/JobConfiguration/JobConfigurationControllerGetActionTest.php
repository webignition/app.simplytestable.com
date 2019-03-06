<?php

namespace App\Tests\Functional\Controller\JobConfiguration;

use App\Entity\User;
use App\Tests\Factory\UserFactory;
use App\Tests\Services\JobConfigurationFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @group Controller/JobConfiguration
 */
class JobConfigurationControllerGetActionTest extends AbstractJobConfigurationControllerTest
{
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
    }

    public function testGetActionGetRequest()
    {
        $jobConfigurationFactory = self::$container->get(JobConfigurationFactory::class);
        $jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $router = self::$container->get('router');
        $requestUrl = $router->generate('jobconfiguration_get', [
            'label' => $jobConfiguration->getLabel(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'user' => $this->user,
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }
}
