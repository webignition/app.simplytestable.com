<?php

namespace App\Tests\Functional\Controller\JobConfiguration;

use App\Entity\Job\Configuration;
use App\Entity\User;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Services\UserService;
use App\Services\WebSiteService;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Job\Configuration as JobConfiguration;

/**
 * @group Controller/JobConfiguration
 */
class JobConfigurationControllerCreateActionTest extends AbstractJobConfigurationControllerTest
{
    public function testCreateActionPostRequest()
    {
        $userFactory = new UserFactory(self::$container);
        $user = $userFactory->createAndActivateUser();

        $router = self::$container->get('router');
        $requestUrl = $router->generate('jobconfiguration_create');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'parameters' => [
                'label' => 'label',
                'website' => 'website value',
                'type' => 'type value',
                'task-configuration' => [
                    'HTML Validation' => [],
                ],
            ],
            'user' => $user,
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertTrue($response->isRedirect('http://localhost/jobconfiguration/label/'));
    }

    public function testCreateActionFailureLabelNotUnique()
    {
        $userFactory = new UserFactory(self::$container);
        $user = $userFactory->create();
        $this->setUser($user);

        $request = new Request([], [
            'label' => 'label value',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        $this->callCreateAction($request, $user);
        $response = $this->callCreateAction($request, $user);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":2,"message":"Label \"label value\" is not unique"}',
            $response->headers->get('x-jobconfigurationcreate-error')
        );
    }

    public function testCreateActionFailureHasExistingJobConfiguration()
    {
        $userFactory = new UserFactory(self::$container);
        $user = $userFactory->create();
        $this->setUser($user);

        $request = new Request([], [
            'label' => 'label value',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        $this->callCreateAction($request, $user);

        $request->request->set('label', 'different label value');

        $response = $this->callCreateAction($request, $user);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":3,"message":"Matching configuration already exists"}',
            $response->headers->get('x-jobconfigurationcreate-error')
        );
    }

    public function testCreateAction()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobConfigurationRepository = $entityManager->getRepository(Configuration::class);

        $userFactory = new UserFactory(self::$container);
        $user = $userFactory->create();
        $this->setUser($user);

        $label = 'label value';

        $request = new Request([], [
            'label' => $label,
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        /* @var RedirectResponse $response */
        $response = $this->callCreateAction($request, $user);

        $this->assertTrue($response->isRedirect('http://localhost/jobconfiguration/label%20value/'));

        $jobConfiguration = $jobConfigurationRepository->findOneBy([
            'label' => $label,
        ]);

        $this->assertInstanceOf(JobConfiguration::class, $jobConfiguration);
    }

    /**
     * @param Request $request
     * @param User $user
     *
     * @return RedirectResponse|Response
     */
    private function callCreateAction(Request $request, User $user)
    {
        return $this->jobConfigurationController->createAction(
            self::$container->get(UserService::class),
            self::$container->get(WebSiteService::class),
            self::$container->get(TaskTypeService::class),
            self::$container->get(JobTypeService::class),
            $user,
            $request
        );
    }
}
