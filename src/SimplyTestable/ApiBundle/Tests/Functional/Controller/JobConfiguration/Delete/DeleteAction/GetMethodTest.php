<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Delete\DeleteAction;

class GetMethodTest extends DeleteTest
{
    public function testGetMethodIsNotAllowed()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_delete_delete', [
            'label' => 'foo',
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(405, $response->getStatusCode());
    }
}
