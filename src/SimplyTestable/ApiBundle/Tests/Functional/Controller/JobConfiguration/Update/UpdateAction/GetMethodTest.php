<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction;

class GetMethodTest extends UpdateTest
{
    public function testGetMethodIsNotAllowed()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_update_update', [
            'label' => 'foo',
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(405, $response->getStatusCode());
    }
}
