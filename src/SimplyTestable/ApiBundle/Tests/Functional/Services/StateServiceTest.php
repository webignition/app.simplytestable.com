<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

class StateServiceTest extends AbstractBaseTestCase
{
    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var string[]
     */
    private $stateNames = array(
        'job-cancelled',
        'job-completed',
        'job-failed-no-sitemap',
        'job-in-progress',
        'job-new',
        'job-preparing',
        'job-queued',
        'job-rejected',
        'job-resolved',
        'job-resolving',
        'task-awaiting-cancellation',
        'task-cancelled',
        'task-completed',
        'task-failed-no-retry-available',
        'task-failed-retry-available',
        'task-failed-retry-limit-reached',
        'task-in-progress',
        'task-queued',
        'task-queued-for-assignment',
        'task-skipped',
        'worker-activation-request-awaiting-verification',
        'worker-activation-request-failed',
        'worker-activation-request-verified',
        'worker-active',
        'worker-deleted',
        'worker-offline',
        'worker-unactivated',
    );

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->stateService = $this->container->get('simplytestable.services.stateservice');
    }

    public function testFetchUnknownState()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            StateService::EXCEPTION_MESSAGE_UNKNOWN_STATE,
            'foo'
        ));
        $this->expectExceptionCode(StateService::EXCEPTION_CODE_UNKNOWN_STATE);

        $this->stateService->fetch('foo');
    }

    /**
     * @dataProvider fetchDataProvider
     *
     * @param string $stateName
     */
    public function testFetch($stateName)
    {
        $state = $this->stateService->fetch($stateName);

        $this->assertInstanceOf(State::class, $state);
    }

    /**
     * @return array
     */
    public function fetchDataProvider()
    {
        $testData = [];

        foreach ($this->stateNames as $stateName) {
            $testData[$stateName] = [
                'stateName' => $stateName,
            ];
        }

        return $testData;
    }

    public function testFetchCollection()
    {
        $states = $this->stateService->fetchCollection($this->stateNames);

        $this->assertEquals($this->stateNames, array_keys($states));

        foreach ($states as $stateIndex => $state) {
            $this->assertInstanceOf(State::class, $state);
        }
    }
}
