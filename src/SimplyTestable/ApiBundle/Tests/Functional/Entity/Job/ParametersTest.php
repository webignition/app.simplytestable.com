<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ParametersTest extends BaseSimplyTestableTestCase
{
    public function testSetPersistGetParameters()
    {
        $job = $this->createJobFactory()->create();
        $job->setParameters(json_encode(array(
            'foo' => 'bar'
        )));

        $this->getJobService()->persistAndFlush($job);
        $this->getJobService()->getManager()->clear();

        $this->assertEquals('{"foo":"bar"}', $job->getParameters());
    }

    public function testUtf8()
    {
        $key = 'key-ɸ';
        $value = 'value-ɸ';

        $job = $this->createJobFactory()->create();
        $job->setParameters(json_encode(array(
            $key => $value
        )));

        $this->getJobService()->persistAndFlush($job);
        $this->getJobService()->getManager()->clear();

        $this->assertEquals('{"key-\u0278":"value-\u0278"}', $job->getParameters());
    }
}
