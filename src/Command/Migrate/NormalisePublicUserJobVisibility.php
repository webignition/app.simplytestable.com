<?php

namespace App\Command\Migrate;

use App\Entity\Job\Job;
use App\Repository\JobRepository;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NormalisePublicUserJobVisibility extends Command
{
    const RETURN_CODE_OK = 0;

    private $userService;
    private $jobRepository;
    private $entityManager;

    public function __construct(
        UserService $userService,
        JobRepository $jobRepository,
        EntityManagerInterface $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->userService = $userService;
        $this->jobRepository = $jobRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:normalise-public-user-job-visibility')
            ->setDescription('Ensure all public user jobs are public');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var Job[] $publicUserPrivateJobs */
        $publicUserPrivateJobs = $this->jobRepository->findBy([
            'user' => $this->userService->getPublicUser(),
            'isPublic' => false,
        ]);

        $flushRequired = false;

        if (!empty($publicUserPrivateJobs)) {
            foreach ($publicUserPrivateJobs as $job) {
                $job->setIsPublic(true);

                $this->entityManager->persist($job);
                $flushRequired = true;
            }
        }

        if ($flushRequired) {
            $this->entityManager->flush();
        }

        return self::RETURN_CODE_OK;
    }
}
