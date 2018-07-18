<?php

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Services\StateService;
use App\Entity\State;

class LoadStates extends Fixture
{
    private $stateDetails = array(
        'job-completed' => null,
        'job-in-progress' => 'job-completed',
        'job-queued' => 'job-in-progress',
        'job-preparing' => 'job-queued',
        'job-new' => 'job-preparing',
        'task-completed' => null,
        'task-in-progress' => 'task-completed',
        'task-queued' => 'task-in-progress',
        'job-cancelled' => null,
        'task-cancelled' => null,
        'worker-activation-request-verified' => null,
        'worker-activation-request-failed' => null,
        'worker-activation-request-awaiting-verification' => 'worker-activation-request-verified',
        'task-awaiting-cancellation' => null,
        'job-failed-no-sitemap' => null,
        'task-queued-for-assignment' => null,
        'task-failed-no-retry-available' => null,
        'task-failed-retry-available' => null,
        'task-failed-retry-limit-reached' => null,
        'task-skipped' => null,
        'worker-active' => null,
        'worker-deleted' => null,
        'worker-offline' => null,
        'worker-unactivated' => null,
        'job-rejected' => null,
        'job-resolving' => null,
        'job-resolved' => null
    );

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @param StateService $stateService
     */
    public function __construct(StateService $stateService)
    {
        $this->stateService = $stateService;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $stateRepository = $manager->getRepository(State::class);

        foreach ($this->stateDetails as $name => $nextStateName) {
            $state = $stateRepository->findOneBy([
                'name' => $name,
            ]);

            if (empty($state)) {
                $state = new State();
                $state->setName($name);

                if (!is_null($nextStateName)) {
                    $state->setNextState($this->stateService->get($nextStateName));
                }

                $manager->persist($state);
                $manager->flush();
            }
        }
    }
}
