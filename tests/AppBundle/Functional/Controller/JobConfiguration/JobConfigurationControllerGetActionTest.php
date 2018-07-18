<?php

namespace Tests\AppBundle\Functional\Controller\JobConfiguration;

use AppBundle\Entity\User;
use Tests\AppBundle\Factory\JobConfigurationFactory;
use Tests\AppBundle\Factory\UserFactory;
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
        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);
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
