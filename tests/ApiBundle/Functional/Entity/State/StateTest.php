<?php

namespace Tests\ApiBundle\Functional\Entity\State;

use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\State;

class StateTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $state = new State();
        $state->setName('foo');

        $entityManager->persist($state);
        $entityManager->flush();

        $this->assertNotNull($state->getId());
    }
}
