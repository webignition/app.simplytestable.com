<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Stripe;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Stripe\Event;

class EventTest extends BaseSimplyTestableTestCase {

    public function testPersist() {
        $event = new Event();
        $event->setIsLive(true);
        $event->setStripeId('evt_1xzXoIFWYFbDCT');
        $event->setType('plan.created');
   
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($event->getId());
    }

}
