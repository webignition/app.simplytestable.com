<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\GetController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobTaskConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JobConfigurationGetControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var GetController
     */
    private $jobConfigurationGetController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobConfigurationGetController = new GetController();
        $this->jobConfigurationGetController->setContainer($this->container);
    }

    public function testRequest()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $user,
        ]);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_get_get', [
            'label' => $jobConfiguration->getLabel(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'user' => $user,
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testGetActionJobConfigurationNotFound()
    {
        $this->setExpectedException(NotFoundHttpException::class);

        $this->jobConfigurationGetController->getAction('foo');
    }

    /**
     * @dataProvider getActionSuccessDataProvider
     *
     * @param array $jobConfigurationValues
     * @param array $expectedResponseData
     */
    public function testGetActionSuccess($jobConfigurationValues, $expectedResponseData)
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();
        $this->setUser($user);

        $jobConfigurationValues[JobConfigurationFactory::KEY_USER] = $user;

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $jobConfiguration = $jobConfigurationFactory->create($jobConfigurationValues);

        $response = $this->jobConfigurationGetController->getAction($jobConfiguration->getLabel());

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals($response->headers->get('content-type'), 'application/json');

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array
     */
    public function getActionSuccessDataProvider()
    {
        return [
            'without task configuration' => [
                'jobConfigurationValues' => [
                    JobConfigurationFactory::KEY_LABEL => 'foo',
                    JobConfigurationFactory::KEY_WEBSITE_URL => 'http://foo.example.com/',
                    JobConfigurationFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                ],
                'expectedResponseData' => [
                'label' => 'foo',
                'user' => 'user@example.com',
                'website' => 'http://foo.example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task_configurations' => [],
                ],
            ],
            'with task configuration' => [
                'jobConfigurationValues' => [
                    JobConfigurationFactory::KEY_LABEL => 'bar',
                    JobConfigurationFactory::KEY_WEBSITE_URL => 'http://bar.example.com/',
                    JobConfigurationFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                    JobConfigurationFactory::KEY_TASK_CONFIGURATIONS => [
                        [
                            JobTaskConfigurationFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                            JobTaskConfigurationFactory::KEY_OPTIONS => [
                                'html-validation-foo' => 'html-validation-bar',
                            ],
                        ],
                        [
                            JobTaskConfigurationFactory::KEY_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'expectedResponseData' => [
                    'label' => 'bar',
                    'user' => 'user@example.com',
                    'website' => 'http://bar.example.com/',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'options' => [
                                'html-validation-foo' => 'html-validation-bar',
                            ],
                            'is_enabled' => true,
                        ],
                        [
                            'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
