<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActivateAction\Success;

use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\User;

abstract class SuccessTest extends BaseControllerJsonTestCase {

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    /**
     * @var User
     */
    protected $user;


    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }


    public function testUserIsEnabled() {
        $this->assertTrue($this->user->isEnabled());
    }



    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'token' => $this->user->getConfirmationToken()
        ];
    }


    /**
     * @return UserPostActivationPropertiesService
     */
    protected function getUserPostActivationPropertiesService() {
        return $this->container->get('simplytestable.services.job.UserPostActivationPropertiesService');
    }

}

