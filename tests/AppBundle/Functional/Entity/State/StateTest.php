<?php

namespace Tests\AppBundle\Functional\Entity\State;

use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Entity\State;

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
