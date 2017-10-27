<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Stripe\Event;

use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Stripe\Event;

class EventTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $event = new Event();
        $event->setIsLive(true);
        $event->setStripeId('evt_1xzXoIFWYFbDCT');
        $event->setType('plan.created');

        $entityManager->persist($event);
        $entityManager->flush();

        $this->assertNotNull($event->getId());
    }

    public function testDataPropertyPersist()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $event = new Event();
        $event->setIsLive(true);
        $event->setStripeId('evt_1xzXoIFWYFbDCT');
        $event->setType('plan.created');
        $event->setStripeEventData('{
            "object": {
              "date": 1371734277,
              "id": "in_23EPWiZtfONeVs",
              "period_start": 1371731195,
              "period_end": 1371734216,
              "lines": {
                "object": "list",
                "count": 1,
                "url": "/v1/invoices/in_23EPWiZtfONeVs/lines",
                "data": [
                  {
                    "id": "su_23ENw6OCpkUTnf",
                    "object": "line_item",
                    "type": "subscription",
                    "livemode": false,
                    "amount": 900,
                    "currency": "gbp",
                    "proration": false,
                    "period": {
                      "start": 1371734216,
                      "end": 1374326216
                    },
                    "quantity": 1,
                    "plan": {
                      "interval": "month",
                      "name": "Personal",
                      "amount": 900,
                      "currency": "gbp",
                      "id": "personal-9",
                      "object": "plan",
                      "livemode": false,
                      "interval_count": 1,
                      "trial_period_days": 30
                    },
                    "description": null
                  }
                ]
              },
              "subtotal": 900,
              "total": 900,
              "customer": "cus_23DaSz7VPHdcZu",
              "object": "invoice",
              "attempted": true,
              "closed": false,
              "paid": false,
              "livemode": false,
              "attempt_count": 1,
              "amount_due": 900,
              "currency": "gbp",
              "starting_balance": 0,
              "ending_balance": 0,
              "next_payment_attempt": 1371824361,
              "charge": null,
              "discount": null
            }
          }');

        $entityManager->persist($event);
        $entityManager->flush();

        $this->assertNotNull($event->getId());
    }

    public function testEmptyDataPropertyGetDataObject()
    {
        $event = new Event();
        $event->setIsLive(true);
        $event->setStripeId('evt_1xzXoIFWYFbDCT');
        $event->setType('plan.created');

        $this->assertNull($event->getStripeEventData());
        $this->assertNull($event->getStripeEventObject());
    }

    public function testDataPropertyGetDataObject()
    {
        $event = new Event();
        $event->setIsLive(true);
        $event->setStripeId('evt_1xzXoIFWYFbDCT');
        $event->setType('plan.created');

        $eventData = array(
            'object' => 'event',
            'type' => 'customer.subscription.deleted',
            'key2' => array(
                'key2key1' => 'key2value1',
                'key2key2' => 'key2value2'
            ),
            'data' => array(
                'object' => array(
                    'object' => 'list'
                )
            )
        );

        $encodedEventData = json_encode($eventData);
        $expectedEventModel = new \webignition\Model\Stripe\Event\Event($encodedEventData);

        $event->setStripeEventData($encodedEventData);

        $this->assertInternalType('string', $event->getStripeEventData());
        $this->assertEquals($encodedEventData, $event->getStripeEventData());

        $this->assertInstanceOf('webignition\Model\Stripe\Event\Event', $event->getStripeEventObject());
        $this->assertEquals($expectedEventModel, $event->getStripeEventObject());
    }
}
