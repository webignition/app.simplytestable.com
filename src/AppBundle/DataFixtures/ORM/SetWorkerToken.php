<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Worker;

class SetWorkerToken extends Fixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $workerRepository = $manager->getRepository(Worker::class);

        /* @var Worker[] $workers */
        $workers = $workerRepository->findAll();

        foreach ($workers as $worker) {
            $workerToken = $worker->getToken();

            if (empty($workerToken)) {
                $worker->setToken(md5(rand()));

                $manager->persist($worker);
                $manager->flush();
            }
        }
    }
}
