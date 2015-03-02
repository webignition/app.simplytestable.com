<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Task\Options;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Type\Options as TaskTypeOptions;

class OptionsTest extends BaseSimplyTestableTestCase {
    
    public function testPersist() {
        $options = new TaskTypeOptions();
        $options->setTaskType($this->getTaskTypeService()->getByName('HTML validation'));
        $options->setOptions([
            'foo' => 'bar'
        ]);

        $this->getManager()->persist($options);
        $this->getManager()->flush();

        $optionsId = $options->getId();

        $this->getManager()->clear();

        $this->assertEquals($optionsId, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Options')->find($optionsId)->getId());
    }
}
