<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\UpdateController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class JobConfigurationUpdateControllerTest extends BaseSimplyTestableTestCase
{
    const LABEL_ONE = 'job-configuration-label-one';
    const LABEL_TWO = 'job-configuration-label-two';

    const WEBSITE_URL_ONE = 'http://one.example.com/';
    const WEBSITE_URL_TWO = 'http://two.example.com/';

    /**
     * @var UpdateController
     */
    private $jobConfigurationUpdateController;

    /**
     * @var JobConfigurationFactory
     */
    private $jobConfigurationFactory;

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

        $this->jobConfigurationUpdateController = new UpdateController();
        $this->jobConfigurationUpdateController->setContainer($this->container);

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->create();
        $this->setUser($this->user);

        $this->jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $this->jobConfiguration = $this->jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
            JobConfigurationFactory::KEY_LABEL => self::LABEL_ONE,
            JobConfigurationFactory::KEY_WEBSITE_URL => self::WEBSITE_URL_ONE,
        ]);

        $this->jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
            JobConfigurationFactory::KEY_LABEL => self::LABEL_TWO,
            JobConfigurationFactory::KEY_WEBSITE_URL => self::WEBSITE_URL_TWO,
        ]);
    }

    public function testGetRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_update_update', ['label' => 'foo']);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->user,
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testPostRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_update_update', [
            'label' => $this->jobConfiguration->getLabel()
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'parameters' => [
                'label' => 'new-label',
            ],
            'user' => $this->user,
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertTrue($response->isRedirect('/jobconfiguration/new-label/'));
    }

    public function testUpdateActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        try {
            $this->jobConfigurationUpdateController->updateAction(new Request(), 'foo');
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
    }

    public function testUpdateActionJobConfigurationNotFound()
    {
        $this->setExpectedException(NotFoundHttpException::class);

        $this->jobConfigurationUpdateController->updateAction(new Request(), 'foo');
    }

    /**
     * @dataProvider updateActionFailureExceptionDataProvider
     *
     * @param array $postData
     * @param array $expectedResponseHeaderError
     */
    public function testUpdateActionFailureException($postData, $expectedResponseHeaderError)
    {
        $response = $this->jobConfigurationUpdateController->updateAction(
            new Request([], $postData),
            $this->jobConfiguration->getLabel()
        );

        $this->assertTrue($response->isClientError());

        $responseHeaderError = json_decode($response->headers->get('X-JobConfigurationUpdate-Error'), true);

        $this->assertEquals($expectedResponseHeaderError, $responseHeaderError);
    }

    /**
     * @return array
     */
    public function updateActionFailureExceptionDataProvider()
    {
        return [
            'non-unique label' => [
                'postData' => [
                    'label' => self::LABEL_TWO,
                ],
                'expectedResponseHeaderError' => [
                    'code' => 2,
                    'message' => 'Label "job-configuration-label-two" is not unique',
                ],
            ],
            'matching configuration already exists' => [
                'postData' => [
                    'label' => self::LABEL_ONE,
                    'website' => self::WEBSITE_URL_TWO,
                ],
                'expectedResponseHeaderError' => [
                    'code' => 3,
                    'message' => 'Matching configuration already exists',
                ],
            ],
        ];
    }

    public function testUpdateActionSuccess()
    {
        $response = $this->jobConfigurationUpdateController->updateAction(
            new Request([], [
                'parameters' => 'foo',
            ]),
            $this->jobConfiguration->getLabel()
        );

        $this->assertTrue($response->isRedirect('/jobconfiguration/job-configuration-label-one/'));
        $this->assertEquals('foo', $this->jobConfiguration->getParameters());
    }
}
