<?php

namespace App\Services\FixtureLoader;

use App\Entity\State;
use App\Services\YamlResourceLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StateFixtureLoader extends AbstractFixtureLoader implements FixtureLoaderInterface
{
    private $resourceLoader;

    public function __construct(EntityManagerInterface $entityManager, YamlResourceLoader $resourceLoader)
    {
        parent::__construct($entityManager);

        $this->resourceLoader = $resourceLoader;
    }

    protected function getEntityClass(): string
    {
        return State::class;
    }

    public function load(?OutputInterface $output = null): void
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

        if ($output) {
            $output->writeln('');
        }
    }
}
