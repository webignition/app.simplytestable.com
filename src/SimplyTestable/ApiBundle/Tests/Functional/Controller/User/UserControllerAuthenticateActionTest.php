<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User;

class UserControllerAuthenticateActionTest extends AbstractUserControllerTest
{
    public function testAuthenticateActionGetRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('user_authenticate', [
            'email_canonical' => 'public@simplytestable.com',
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }
}
