<?php

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Services\StateService;
use App\Entity\State;

class LoadStates extends Fixture
{
    private $stateDetails = array(
        'job-completed',
        'job-in-progress',
        'job-queued',
        'job-preparing',
        'job-new',
        'task-completed',
        'task-in-progress',
        'task-queued',
        'job-cancelled',
        'task-cancelled',
        'worker-activation-request-verified',
        'worker-activation-request-failed',
        'worker-activation-request-awaiting-verification',
        'task-awaiting-cancellation',
        'job-failed-no-sitemap',
        'task-queued-for-assignment',
        'task-failed-no-retry-available',
        'task-failed-retry-available',
        'task-failed-retry-limit-reached',
        'task-skipped',
        'worker-active',
        'worker-deleted',
        'worker-offline',
        'worker-unactivated',
        'job-rejected',
        'job-resolving',
        'job-resolved',
        'job-expired',
        'task-expired',
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

        foreach ($this->stateDetails as $name) {
            $state = $stateRepository->findOneBy([
                'name' => $name,
            ]);

            if (empty($state)) {
                $state = State::create($name);

                $manager->persist($state);
                $manager->flush();
            }
        }
    }
}
