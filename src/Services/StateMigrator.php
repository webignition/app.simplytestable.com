<?php

namespace App\Services;

use App\Entity\State;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Output\OutputInterface;

class StateMigrator
{
    private $resourceLoader;
    private $entityManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $repository;

    public function __construct(YamlResourceLoader $resourceLoader, EntityManagerInterface $entityManager)
    {
        $this->resourceLoader = $resourceLoader;
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(State::class);
    }

    public function migrate(?OutputInterface $output = null)
    {
        if ($output) {
            $output->writeln('Migrating states ...');
        }

        $names = $this->resourceLoader->getData();

        $currentEntity = '';

        foreach ($names as $name) {
            $entity = explode('-', $name, 2)[0];

            if ($currentEntity !== $entity) {
                $currentEntity = $entity;

                if ($output) {
                    $output->writeln([
                        '',
                        $currentEntity,
                    ]);
                }
            }

            if ($output) {
                $output->write("  " . '<comment>' . $name . '</comment> ...');
            }

            $entity = $this->repository->findOneBy([
                'name' => $name,
            ]);

            if (!$entity) {
                if ($output) {
                    $output->write(' <fg=cyan>creating</>');
                }

                $entity = State::create($name);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
            }

            if ($output) {
                $output->writeln(' <info>✓</info>');
            }
        }
    }
}