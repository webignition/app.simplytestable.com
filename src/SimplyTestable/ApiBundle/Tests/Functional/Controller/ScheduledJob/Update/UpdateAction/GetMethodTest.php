<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction;

class GetMethodTest extends UpdateTest
{
    public function testGetMethodIsNotAllowed()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_update_update', [
            'id' => 1,
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(405, $response->getStatusCode());
    }
}
