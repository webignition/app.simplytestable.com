<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Get\GetAction\Success;

use SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Get\GetAction\GetTest;
use Symfony\Component\HttpFoundation\Response;

class SuccessTest extends GetTest {

    /**
     * @var Response
     */
    private $response;


    private $decodedResponse;

    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->createAndActivateUser('user@example.com'));

        $methodName = $this->getActionNameFromRouter();

        $this->getJobConfigurationCreateController('createAction', [
            'label' => 'foo',
            'website' => 'http://example.com/',
            'type' => 'Full site',
            'task-configuration' => [
                'HTML validation' => [],
                'CSS validation' => [
                    'domains-to-ignore' => [
                        'one.cdn.example.com'
                    ]
                ]
            ],
            'parameters' => json_encode([
                'http-auth-username' => 'html-user',
                'http-auth-password' => 'html-password',
                'cookies' => [
                    [
                        'Name' => 'cookie-name',
                        'Domain' => '.example.com',
                        'Value' => 'cookie-value'
                    ]
                ]
            ])
        ])->createAction(
            $this->container->get('request')
        );


        $this->response = $this->getCurrentController()->$methodName('foo');
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