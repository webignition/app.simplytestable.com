<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Stripe;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Stripe\Event;

class EventTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8StripeId() {
        $stripeId = 'foo-ɸ';
        
        $event = new Event();
        $event->setIsLive(true);
        $event->setStripeId($stripeId);
        $event->setType('plan.created');
   
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
      
        $eventId = $event->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($stripeId, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Stripe\Event')->find($eventId)->getStripeId());
    }
    
    public function testUtf8Type() {
        $type = 'foo-ɸ';
        
        $event = new Event();
        $event->setIsLive(true);
        $event->setStripeId('stripe-id-foo');
        $event->setType($type);
   
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
      
        $eventId = $event->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($type, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Stripe\Event')->find($eventId)->getType());
    }
    
    public function testUtf8Data() {
        $data = 'foo-ɸ';
        
        $event = new Event();
        $event->setIsLive(true);
        $event->setStripeId('stripe-id-foo');
        $event->setType('foo-type');
        $event->setData($data);
   
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
      
        $eventId = $event->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($data, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Stripe\Event')->find($eventId)->getData());
    }   

//    public function testPersist() {
//        $event = new Event();
//        $event->setIsLive(true);
//        $event->setStripeId('evt_1xzXoIFWYFbDCT');
//        $event->setType('plan.created');
//   
//        $this->getEntityManager()->persist($event);
//        $this->getEntityManager()->flush();
//        
//        $this->assertNotNull($event->getId());
//    }
//    
//    
//    public function testDataPropertyPersist() {
//        $event = new Event();
//        $event->setIsLive(true);
//        $event->setStripeId('evt_1xzXoIFWYFbDCT');
//        $event->setType('plan.created');
//        $event->setData('{
//            "object": {
//              "date": 1371734277,
//              "id": "in_23EPWiZtfONeVs",
//              "period_start": 1371731195,
//              "period_end": 1371734216,
//              "lines": {
//                "object": "list",
//                "count": 1,
//                "url": "/v1/invoices/in_23EPWiZtfONeVs/lines",
//                "data": [
//                  {
//                    "id": "su_23ENw6OCpkUTnf",
//                    "object": "line_item",
//                    "type": "subscription",
//                    "livemode": false,
//                    "amount": 900,
//                    "currency": "gbp",
//                    "proration": false,
//                    "period": {
//                      "start": 1371734216,
//                      "end": 1374326216
//                    },
//                    "quantity": 1,
//                    "plan": {
//                      "interval": "month",
//                      "name": "Personal",
//                      "amount": 900,
//                      "currency": "gbp",
//                      "id": "personal-9",
//                      "object": "plan",
//                      "livemode": false,
//                      "interval_count": 1,
//                      "trial_period_days": 30
//                    },
//                    "description": null
//                  }
//                ]
//              },
//              "subtotal": 900,
//              "total": 900,
//              "customer": "cus_23DaSz7VPHdcZu",
//              "object": "invoice",
//              "attempted": true,
//              "closed": false,
//              "paid": false,
//              "livemode": false,
//              "attempt_count": 1,
//              "amount_due": 900,
//              "currency": "gbp",
//              "starting_balance": 0,
//              "ending_balance": 0,
//              "next_payment_attempt": 1371824361,
//              "charge": null,
//              "discount": null
//            }
//          }');
//   
//        $this->getEntityManager()->persist($event);
//        $this->getEntityManager()->flush();
//        
//        $this->assertNotNull($event->getId());        
//    }
//    
//    public function testEmptyDataPropertyGetDataObject() {
//        $event = new Event();
//        $event->setIsLive(true);
//        $event->setStripeId('evt_1xzXoIFWYFbDCT');
//        $event->setType('plan.created');
//        
//        $this->assertNull($event->getData());
//        $this->assertNull($event->getDataObject());
//    }    
//    
//    public function testDataPropertyGetDataObject() {
//        $event = new Event();
//        $event->setIsLive(true);
//        $event->setStripeId('evt_1xzXoIFWYFbDCT');
//        $event->setType('plan.created');
//        
//        $testData = array(
//            'key1' => 'value1',
//            'key2' => array(
//                'key2key1' => 'key2value1',
//                'key2key2' => 'key2value2'
//            ),
//            'key3' => 'value3'
//        );
//        
//        $event->setData(json_encode($testData));
//        
//        $this->assertInternalType('string', $event->getData());
//        $this->assertEquals(json_encode($testData), $event->getData());
//        $this->assertInstanceOf('\stdClass', $event->getDataObject());
//        $this->assertEquals(json_decode(json_encode($testData)), $event->getDataObject());
//        $this->assertEquals('key2value1', $event->getDataObject()->key2->key2key1);
//    }      

}
