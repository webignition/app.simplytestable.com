<?php
namespace App\Services\Job;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\Job\Configuration as JobConfiguration;
use App\Entity\Job\Configuration;
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

        $this->jobConfigurationRepository = $entityManager->getRepository(Configuration::class);
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

        if ($values->hasEmptyLabel()) {
            throw new JobConfigurationServiceException(
                'Label cannot be empty',
                JobConfigurationServiceException::CODE_LABEL_CANNOT_BE_EMPTY
            );
        }

        if (!empty($this->getByLabel($values->getLabel()))) {
            throw new JobConfigurationServiceException(
                'Label "' . $values->getLabel() . '" is not unique',
                JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
            );
        }

        if (!$values->hasWebsite()) {
            throw new JobConfigurationServiceException(
                'Website cannot be empty',
                JobConfigurationServiceException::CODE_WEBSITE_CANNOT_BE_EMPTY
            );
        }

        if (!$values->hasType()) {
            throw new JobConfigurationServiceException(
                'Type cannot be empty',
                JobConfigurationServiceException::CODE_TYPE_CANNOT_BE_EMPTY
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

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setLabel($values->getLabel());
        $jobConfiguration->setUser($user);
        $jobConfiguration->setWebsite($values->getWebsite());
        $jobConfiguration->setType($values->getType());
        $jobConfiguration->setParameters($values->getParameters());

        $this->entityManager->persist($jobConfiguration);

        foreach ($values->getTaskConfigurationCollection()->get() as $taskConfiguration) {
            /* @var $taskConfiguration TaskConfiguration */
            $taskConfiguration->setJobConfiguration($jobConfiguration);
            $jobConfiguration->addTaskConfiguration($taskConfiguration);
            $this->entityManager->persist($taskConfiguration);
        }

        $this->entityManager->persist($jobConfiguration);
        $this->entityManager->flush();

        return $jobConfiguration;
    }

    public function getById(int $id): ?JobConfiguration
    {
        $user = $this->tokenStorage->getToken()->getUser();

        /* @var Configuration $jobConfiguration */
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

        /* @var Configuration $jobConfiguration */
        $jobConfiguration = $this->jobConfigurationRepository->findOneBy([
            'label' => $label,
            'user' => ($this->teamService->hasForUser($user))
                ? $this->teamService->getPeopleForUser($user)
                : [$user]
        ]);

        return $jobConfiguration;
    }

    /**
     * @param Configuration $jobConfiguration
     * @param ConfigurationValues $newValues
     *
     * @return Configuration
     *
     * @throws JobConfigurationServiceException
     */
    public function update(JobConfiguration $jobConfiguration, ConfigurationValues $newValues)
    {
        if ($newValues->hasNonEmptyLabel()) {
            $existingConfiguration = $this->getByLabel($newValues->getLabel());

            if ($existingConfiguration && $existingConfiguration->getId() !== $jobConfiguration->getId()) {
                throw new JobConfigurationServiceException(
                    'Label "' . $newValues->getLabel() . '" is not unique',
                    JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
                );
            }
        }

        $comparatorValues = clone $newValues;

        if (!$newValues->hasWebsite()) {
            $comparatorValues->setWebsite($jobConfiguration->getWebsite());
        }

        if (!$newValues->hasType()) {
            $comparatorValues->setType($jobConfiguration->getType());
        }

        if (!$newValues->hasTaskConfigurationCollection()) {
            $comparatorValues->setTaskConfigurationCollection($jobConfiguration->getTaskConfigurationsAsCollection());
        }

        if (!$newValues->hasParameters()) {
            $comparatorValues->setParameters($jobConfiguration->getParameters());
        }

        if ($this->matches($jobConfiguration, $comparatorValues)) {
            $comparatorValuesHasLabelChange = $jobConfiguration->getLabel() !== $comparatorValues->getLabel();
            $hasLabelChange = $comparatorValues->hasEmptyLabel() || $comparatorValuesHasLabelChange;

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

        if ($newValues->hasNonEmptyLabel()) {
            $jobConfiguration->setLabel($newValues->getLabel());
        }

        if ($newValues->hasWebsite()) {
            $jobConfiguration->setWebsite($newValues->getWebsite());
        }

        if ($newValues->hasType()) {
            $jobConfiguration->setType($newValues->getType());
        }

        if ($newValues->hasParameters()) {
            $jobConfiguration->setParameters($newValues->getParameters());
        }

        if ($newValues->hasTaskConfigurationCollection()) {
            foreach ($jobConfiguration->getTaskConfigurations() as $oldTaskConfiguration) {
                $this->entityManager->remove($oldTaskConfiguration);
            }

            $jobConfiguration->getTaskConfigurations()->clear();

            foreach ($newValues->getTaskConfigurationCollection()->get() as $taskConfiguration) {
                /* @var $taskConfiguration TaskConfiguration */
                $taskConfiguration->setJobConfiguration($jobConfiguration);
                $jobConfiguration->addTaskConfiguration($taskConfiguration);
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

        foreach ($configuration->getTaskConfigurations() as $taskConfiguration) {
            $this->entityManager->remove($taskConfiguration);
        }

        try {
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $foo) {
            throw new JobConfigurationServiceException(
                'Job configuration is in use by one or more scheduled jobs',
                JobConfigurationServiceException::CODE_IS_IN_USE_BY_SCHEDULED_JOB
            );
        }
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
            foreach ($userJobConfiguration->getTaskConfigurations() as $jobTaskConfiguration) {
                $this->entityManager->remove($jobTaskConfiguration);
                $userJobConfiguration->removeTaskConfiguration($jobTaskConfiguration);
            }

            $this->entityManager->remove($userJobConfiguration);

            try {
                $this->entityManager->flush($userJobConfiguration);
            } catch (ForeignKeyConstraintViolationException $foo) {
                throw new JobConfigurationServiceException(
                    'One or more job configurations are in use by one or more scheduled jobs',
                    JobConfigurationServiceException::CODE_IS_IN_USE_BY_SCHEDULED_JOB
                );
            }
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
                $jobConfiguration->getTaskConfigurationsAsCollection()
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

        if (!$jobConfiguration->getTaskConfigurationsAsCollection()->equals(
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
