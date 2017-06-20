<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\State;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\State;

class StateTest extends BaseSimplyTestableTestCase {

    public function testUtf8Name() {
        $name = 'test-ɸ';

        $state = new State();
        $state->setName($name);

        $this->getManager()->persist($state);
        $this->getManager()->flush();

        $stateId = $state->getId();

        $this->getManager()->clear();

        $this->assertEquals($name, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->find($stateId)->getName());
    }
}
