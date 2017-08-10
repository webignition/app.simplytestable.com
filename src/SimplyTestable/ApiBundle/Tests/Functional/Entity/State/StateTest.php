<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\State;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\State;

class StateTest extends BaseSimplyTestableTestCase
{
    public function testPersist()
    {
        $state = new State();
        $state->setName('foo');

        $this->getManager()->persist($state);
        $this->getManager()->flush();

        $this->assertNotNull($state->getId());
    }
}
