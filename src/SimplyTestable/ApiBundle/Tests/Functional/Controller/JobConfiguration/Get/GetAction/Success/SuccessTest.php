<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Get\GetAction\Success;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\GetController;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobTaskConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Get\GetAction\GetTest;
use Symfony\Component\HttpFoundation\Response;

class SuccessTest extends GetTest {

    /**
     * @var Response
     */
    private $response;


    private $decodedResponse;

    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();
        $this->setUser($user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $user,
            JobConfigurationFactory::KEY_LABEL => 'foo',
            JobConfigurationFactory::KEY_WEBSITE_URL => 'http://example.com/',
            JobConfigurationFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
            JobConfigurationFactory::KEY_TASK_CONFIGURATIONS => [
                [
                    JobTaskConfigurationFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                ],
                [
                    JobTaskConfigurationFactory::KEY_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                    JobTaskConfigurationFactory::KEY_OPTIONS => [
                        'domains-to-ignore' => [
                            'one.cdn.example.com'
                        ]
                    ],
                ],
            ],
            JobConfigurationFactory::KEY_PARAMETERS => [
                'http-auth-username' => 'html-user',
                'http-auth-password' => 'html-password',
                'cookies' => [
                    [
                        'Name' => 'cookie-name',
                        'Domain' => '.example.com',
                        'Value' => 'cookie-value'
                    ]
                ],
            ],
        ]);

        $controller = new GetController();
        $controller->setContainer($this->container);

        $this->response = $controller->getAction('foo');
        $this->decodedResponse = json_decode($this->response->getContent(), true);
    }

    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }


    public function testDecodedResponseContent() {
        $this->assertEquals([
            'label' => 'foo',
            'user' => 'user@example.com',
            'website' => 'http://example.com/',
            'type' => 'Full site',
            'task_configurations' => [
                [
                    'type' => 'HTML validation',
                    'options' => [
                    ],
                    'is_enabled' => true
                ],
                [
                    'type' => 'CSS validation',
                    'options' => [
                        'domains-to-ignore' => [
                            'one.cdn.example.com'
                        ]
                    ],
                    'is_enabled' => true
                ],
            ],
            'parameters' => '{"http-auth-username":"html-user","http-auth-password":"html-password","cookies":[{"Name":"cookie-name","Domain":".example.com","Value":"cookie-value"}]}'
        ], $this->decodedResponse);
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'label' => 'foo'
        ];
    }
}