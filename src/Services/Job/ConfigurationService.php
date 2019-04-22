<?php

namespace App\Services\Job;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\Job\Configuration as JobConfiguration;
use App\Entity\Job\TaskConfiguration as TaskConfiguration;
use App\Services\Team\Service as TeamService;
use App\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use App\Model\Job\Configuration\Values as ConfigurationValues;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ConfigurationService
{
    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $jobConfigurationRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TeamService $teamService
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TeamService $teamService,
        TokenStorageInterface $tokenStorage
    ) {
        $this->entityManager = $entityManager;
        $this->teamService = $teamService;
        $this->tokenStorage = $tokenStorage;

        $this->jobConfigurationRepository = $entityManager->getRepository(JobConfiguration::class);
    }

    /**
     * @param ConfigurationValues $values
     *
     * @return JobConfiguration
     * @throws JobConfigurationServiceException
     */
    public function create(ConfigurationValues $values)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (!empty($this->getByLabel($values->getLabel()))) {
            throw new JobConfigurationServiceException(
                'Label "' . $values->getLabel() . '" is not unique',
                JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
            );
        }

        if ($values->getTaskConfigurationCollection()->isEmpty()) {
            throw new JobConfigurationServiceException(
                'TaskConfigurationCollection is empty',
                JobConfigurationServiceException::CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY
            );
        }

        if ($this->hasExisting($values)) {
            throw new JobConfigurationServiceException(
                'Matching configuration already exists',
                JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
            );
        }

        $jobConfiguration = JobConfiguration::create(
            $values->getLabel(),
            $user,
            $values->getWebsite(),
            $values->getType(),
            $values->getTaskConfigurationCollection(),
            $values->getParameters()
        );

        $this->entityManager->persist($jobConfiguration);

        foreach ($values->getTaskConfigurationCollection() as $taskConfiguration) {
            /* @var $taskConfiguration TaskConfiguration */
            $taskConfiguration->setJobConfiguration($jobConfiguration);
            $this->entityManager->persist($taskConfiguration);
        }

        $this->entityManager->persist($jobConfiguration);
        $this->entityManager->flush();

        return $jobConfiguration;
    }

    public function getById(int $id): ?JobConfiguration
    {
        $user = $this->tokenStorage->getToken()->getUser();

        /* @var JobConfiguration $jobConfiguration */
        $jobConfiguration = $this->jobConfigurationRepository->findOneBy([
            'id' => $id,
            'user' => ($this->teamService->hasForUser($user))
                ? $this->teamService->getPeopleForUser($user)
                : [$user]
        ]);

        return $jobConfiguration;
    }

    public function getByLabel(string $label): ?JobConfiguration
    {
        $user = $this->tokenStorage->getToken()->getUser();

        /* @var JobConfiguration $jobConfiguration */
        $jobConfiguration = $this->jobConfigurationRepository->findOneBy([
            'label' => $label,
            'user' => ($this->teamService->hasForUser($user))
                ? $this->teamService->getPeopleForUser($user)
                : [$user]
        ]);

        return $jobConfiguration;
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @param ConfigurationValues $newValues
     *
     * @return JobConfiguration
     *
     * @throws JobConfigurationServiceException
     */
    public function update(JobConfiguration $jobConfiguration, ConfigurationValues $newValues)
    {
        if (!empty($newValues->getLabel())) {
            $existingConfiguration = $this->getByLabel($newValues->getLabel());

            if ($existingConfiguration && $existingConfiguration->getId() !== $jobConfiguration->getId()) {
                throw new JobConfigurationServiceException(
                    'Label "' . $newValues->getLabel() . '" is not unique',
                    JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
                );
            }
        }

        $comparatorValues = clone $newValues;

        if ($this->matches($jobConfiguration, $comparatorValues)) {
            $comparatorLabel = $comparatorValues->getLabel();

            $comparatorValuesHasLabelChange = $jobConfiguration->getLabel() !== $comparatorLabel;
            $hasLabelChange = empty($comparatorLabel) || $comparatorValuesHasLabelChange;

            if (!$hasLabelChange) {
                return $jobConfiguration;
            }
        } else {
            if ($this->hasExisting($comparatorValues)) {
                throw new JobConfigurationServiceException(
                    'Matching configuration already exists',
                    JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
                );
            }
        }

        $jobConfiguration->update(
            $newValues->getLabel(),
            $newValues->getWebsite(),
            $newValues->getType(),
            $newValues->getParameters()
        );

        $newTaskConfigurationCollection = $newValues->getTaskConfigurationCollection();

        if (!$newTaskConfigurationCollection->isEmpty()) {
            foreach ($jobConfiguration->getTaskConfigurationCollection() as $oldTaskConfiguration) {
                $this->entityManager->remove($oldTaskConfiguration);
            }

            $jobConfiguration->clearTaskConfigurationCollection();
            $jobConfiguration->setTaskConfigurationCollection($newTaskConfigurationCollection);

            foreach ($newTaskConfigurationCollection as $taskConfiguration) {
                $this->entityManager->persist($taskConfiguration);
            }
        }

        $this->entityManager->persist($jobConfiguration);
        $this->entityManager->flush();

        return $jobConfiguration;
    }

    /**
     * @param int $id
     *
     * @throws JobConfigurationServiceException
     */
    public function delete(int $id)
    {
        $configuration = $this->getById($id);

        if (empty($configuration)) {
            throw new JobConfigurationServiceException(
                'Configuration does not exist',
                JobConfigurationServiceException::CODE_NO_SUCH_CONFIGURATION
            );
        }

        $this->entityManager->remove($configuration);

        foreach ($configuration->getTaskConfigurationCollection() as $taskConfiguration) {
            $this->entityManager->remove($taskConfiguration);
        }

        $this->entityManager->flush();
    }

    /**
     * @return JobConfiguration[]
     */
    public function getList(): array
    {
        $user = $this->tokenStorage->getToken()->getUser();

        return $this->jobConfigurationRepository->findBy([
            'user' => ($this->teamService->hasForUser($user))
                ? $this->teamService->getPeopleForUser($user)
                : [$user]
        ]);
    }

    /**
     * @throws JobConfigurationServiceException
     */
    public function removeAll()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if ($this->teamService->hasForUser($user)) {
            throw new JobConfigurationServiceException(
                'Unable to remove all; user is in a team',
                JobConfigurationServiceException::CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM
            );
        }

        $userJobConfigurations = $this->jobConfigurationRepository->findBy([
            'user' => $user
        ]);

        foreach ($userJobConfigurations as $userJobConfiguration) {
            /* @var $userJobConfiguration JobConfiguration */
            foreach ($userJobConfiguration->getTaskConfigurationCollection() as $jobTaskConfiguration) {
                $this->entityManager->remove($jobTaskConfiguration);
            }

            $userJobConfiguration->clearTaskConfigurationCollection();
            $this->entityManager->remove($userJobConfiguration);

            $this->entityManager->flush();
        }
    }

    /**
     * @param ConfigurationValues $values
     * @return bool
     */
    private function hasExisting(ConfigurationValues $values)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $jobConfigurations = $this->jobConfigurationRepository->findBy([
            'website' => $values->getWebsite(),
            'type' => $values->getType(),
            'parameters' => $values->getParameters(),
            'user' => ($this->teamService->hasForUser($user))
                ? $this->teamService->getPeopleForUser($user)
                : [$user]
        ]);

        foreach ($jobConfigurations as $jobConfiguration) {
            /* @var $jobConfiguration JobConfiguration */
            if ($values->getTaskConfigurationCollection()->equals(
                $jobConfiguration->getTaskConfigurationCollection()
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @param ConfigurationValues $values
     *
     * @return bool
     */
    private function matches(JobConfiguration $jobConfiguration, ConfigurationValues $values)
    {
        if ($jobConfiguration->getParameters() != $values->getParameters()) {
            return false;
        }

        if (!$jobConfiguration->getTaskConfigurationCollection()->equals(
            $values->getTaskConfigurationCollection()
        )) {
            return false;
        }

        if (!$jobConfiguration->getType()->equals($values->getType())) {
            return false;
        }

        if (!$jobConfiguration->getWebsite()->equals($values->getWebsite())) {
            return false;
        }

        return true;
    }
}
