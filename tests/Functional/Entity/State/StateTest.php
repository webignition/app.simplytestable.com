<?php

namespace App\Tests\Functional\Entity\State;

use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\State;
use App\Tests\Services\ObjectReflector;

class StateTest extends AbstractBaseTestCase
{
    public function testPersist()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $state = State::create('foo');

        $entityManager->persist($state);
        $entityManager->flush();

        $this->assertNotNull(ObjectReflector::getProperty($state, 'id'));
    }
}
