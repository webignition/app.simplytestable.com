<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\State;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\State;

class StateTest extends BaseSimplyTestableTestCase
{
    public function testPersist()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $state = new State();
        $state->setName('foo');

        $entityManager->persist($state);
        $entityManager->flush();

        $this->assertNotNull($state->getId());
    }
}
