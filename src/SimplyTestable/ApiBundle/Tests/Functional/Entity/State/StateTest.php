<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\State;

use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\State;

class StateTest extends AbstractBaseTestCase
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
