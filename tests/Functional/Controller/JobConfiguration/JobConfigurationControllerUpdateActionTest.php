<?php

namespace App\Tests\Functional\Controller\JobConfiguration;

use App\Entity\User;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Services\WebSiteService;
use App\Tests\Services\JobConfigurationFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Job\Configuration as JobConfiguration;

/**
 * @group Controller/JobConfiguration
 */
class JobConfigurationControllerUpdateActionTest extends AbstractJobConfigurationControllerTest
{
    const LABEL_ONE = 'job-configuration-label-one';
    const LABEL_TWO = 'job-configuration-label-two';

    const WEBSITE_URL_ONE = 'http://one.example.com/';
    const WEBSITE_URL_TWO = 'http://two.example.com/';

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

        $userFactory = new UserFactory(self::$container);
        $this->user = $userFactory->createAndActivateUser();
        $this->setUser($this->user);

        $this->jobConfigurationFactory = self::$container->get(JobConfigurationFactory::class);
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

    public function testUpdateActionGetRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('jobconfiguration_update', ['label' => 'foo']);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->user,
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testUpdateActionPostRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('jobconfiguration_update', [
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

        $this->assertTrue($response->isRedirect('http://localhost/jobconfiguration/new-label/'));
    }

    /**
     * @dataProvider updateActionFailureExceptionDataProvider
     *
     * @param array $postData
     * @param array $expectedResponseHeaderError
     */
    public function testUpdateActionFailureException($postData, $expectedResponseHeaderError)
    {
        $response = $this->callUpdateAction($postData);

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
        $response = $this->callUpdateAction([
            'parameters' => 'foo',
        ]);

        $this->assertTrue($response->isRedirect('http://localhost/jobconfiguration/job-configuration-label-one/'));
        $this->assertEquals('foo', $this->jobConfiguration->getParameters());
    }

    /**
     * @param array $postData
     *
     * @return RedirectResponse|Response
     */
    private function callUpdateAction($postData)
    {
        return $this->jobConfigurationController->updateAction(
            self::$container->get(WebSiteService::class),
            self::$container->get(TaskTypeService::class),
            self::$container->get(JobTypeService::class),
            new Request([], $postData),
            $this->jobConfiguration->getLabel()
        );
    }
}
