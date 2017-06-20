<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Stripe\WebHookController\IndexAction;

class ErrorCasesTest extends IndexActionTest {

    public function testWithNoRequestBody() {
        $this->assertEquals(400, $this->getStripeWebHookController('indexAction')->indexAction()->getStatusCode());
    }


    public function testWithNoStripeEvent() {
        $this->assertEquals(400, $this->getStripeWebHookController('indexAction', array(
            'event' => 'eventdata but this is not a stripe JSON object by any means'
        ))->indexAction()->getStatusCode());
    }

}


