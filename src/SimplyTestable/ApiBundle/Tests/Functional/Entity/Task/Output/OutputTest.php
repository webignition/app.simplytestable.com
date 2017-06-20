<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Task\Output;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use webignition\InternetMediaType\InternetMediaType;

class TaskTest extends BaseSimplyTestableTestCase {

    public function testUtf8Output() {
        $outputValue = 'ɸ';

        $output = new Output();
        $output->setOutput($outputValue);

        $this->getManager()->persist($output);
        $this->getManager()->flush();

        $outputId = $output->getId();

        $this->getManager()->clear();

        $this->assertEquals($outputValue, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output')->find($outputId)->getOutput());
    }

    public function testUtf8ContentType() {
        $typeValue = 'ɸ';

        $contentType = new InternetMediaType();
        $contentType->setType($typeValue);
        $contentType->setSubtype($typeValue);

        $output = new Output();
        $output->setOutput('');
        $output->setContentType($contentType);


        $this->getManager()->persist($output);
        $this->getManager()->flush();

        $outputId = $output->getId();

        $this->getManager()->clear();
        $this->assertEquals($typeValue . '/' . $typeValue, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output')->find($outputId)->getContentType());
    }

    public function testUtf8Hash() {
        $hash = 'ɸ';

        $output = new Output();
        $output->setOutput('');
        $output->setHash($hash);

        $this->getManager()->persist($output);
        $this->getManager()->flush();

        $outputId = $output->getId();

        $this->getManager()->clear();

        $this->assertEquals($hash, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output')->find($outputId)->getHash());
    }
}
