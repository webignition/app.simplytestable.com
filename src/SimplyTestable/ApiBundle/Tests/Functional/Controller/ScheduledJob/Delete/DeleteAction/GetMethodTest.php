<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Delete\DeleteAction;

class GetMethodTest extends DeleteTest
{
    public function testGetMethodIsNotAllowed()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_delete_delete', [
            'id' => 1,
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(405, $response->getStatusCode());
    }
}
