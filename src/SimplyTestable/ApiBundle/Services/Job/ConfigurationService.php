<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;
use SimplyTestable\ApiBundle\Services\EntityService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ConfigurationService extends EntityService
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
     * @param EntityManager $entityManager
     * @param TeamService $teamService
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EntityManager $entityManager,
        TeamService $teamService,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($entityManager);

        $this->teamService = $teamService;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return Configuration::class;
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

        if ($this->has($values->getLabel())) {
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

        $this->getManager()->persist($jobConfiguration);

        foreach ($values->getTaskConfigurationCollection()->get() as $taskConfiguration) {
            /* @var $taskConfiguration TaskConfiguration */
            $taskConfiguration->setJobConfiguration($jobConfiguration);
            $jobConfiguration->addTaskConfiguration($taskConfiguration);
            $this->getManager()->persist($taskConfiguration);
        }

        $this->getManager()->persist($jobConfiguration);
        $this->getManager()->flush();

        return $jobConfiguration;
    }

    /**
     * @param $label
     * @throws JobConfigurationServiceException
     * @return null|JobConfiguration
     */
    public function get($label)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        /* @var Configuration $jobConfiguration */
        $jobConfiguration = $this->getEntityRepository()->findOneBy([
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
     * @throws JobConfigurationServiceException
     */
    public function update(JobConfiguration $jobConfiguration, ConfigurationValues $newValues)
    {
        if ($newValues->hasNonEmptyLabel()) {
            if ($this->has($newValues->getLabel()) && $this->get($newValues->getLabel()) != $jobConfiguration) {
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
            if (!$this->hasLabelChange($jobConfiguration, $comparatorValues)) {
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
                $this->getManager()->remove($oldTaskConfiguration);
            }

            $jobConfiguration->getTaskConfigurations()->clear();

            foreach ($newValues->getTaskConfigurationCollection()->get() as $taskConfiguration) {
                /* @var $taskConfiguration TaskConfiguration */
                $taskConfiguration->setJobConfiguration($jobConfiguration);
                $jobConfiguration->addTaskConfiguration($taskConfiguration);
                $this->getManager()->persist($taskConfiguration);
            }
        }

        $this->getManager()->persist($jobConfiguration);
        $this->getManager()->flush();

        return $jobConfiguration;
    }

    /**
     * @param string $label
     *
     * @throws JobConfigurationServiceException
     */
    public function delete($label)
    {
        if (!$this->has($label)) {
            throw new JobConfigurationServiceException(
                'Configuration with label "' . $label . '" does not exist',
                JobConfigurationServiceException::CODE_NO_SUCH_CONFIGURATION
            );
        }

        $configuration = $this->get($label);
        $this->getManager()->remove($configuration);

        foreach ($configuration->getTaskConfigurations() as $taskConfiguration) {
            $this->getManager()->remove($taskConfiguration);
        }

        try {
            $this->getManager()->flush();
        } catch (ForeignKeyConstraintViolationException $foo) {
            throw new JobConfigurationServiceException(
                'Job configuration is in use by one or more scheduled jobs',
                JobConfigurationServiceException::CODE_IS_IN_USE_BY_SCHEDULED_JOB
            );
        }
    }

    /**
     * @return JobConfiguration[]
     * @throws JobConfigurationServiceException
     */
    public function getList()
    {
        $user = $this->tokenStorage->getToken()->getUser();

        return $this->getEntityRepository()->findBy([
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

        $userJobConfigurations = $this->getEntityRepository()->findBy([
            'user' => $user
        ]);

        foreach ($userJobConfigurations as $userJobConfiguration) {
            /* @var $userJobConfiguration JobConfiguration */
            foreach ($userJobConfiguration->getTaskConfigurations() as $jobTaskConfiguration) {
                $this->getManager()->remove($jobTaskConfiguration);
                $userJobConfiguration->removeTaskConfiguration($jobTaskConfiguration);
            }

            $this->getManager()->remove($userJobConfiguration);

            try {
                $this->getManager()->flush($userJobConfiguration);
            } catch (ForeignKeyConstraintViolationException $foo) {
                throw new JobConfigurationServiceException(
                    'One or more job configurations are in use by one or more scheduled jobs',
                    JobConfigurationServiceException::CODE_IS_IN_USE_BY_SCHEDULED_JOB
                );
            }

        }
    }

    /**
     * @param $label
     *
     * @return bool
     */
    private function has($label)
    {
        return !is_null($this->get($label));
    }

    /**
     * @param ConfigurationValues $values
     * @return bool
     */
    private function hasExisting(ConfigurationValues $values)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        $jobConfigurations = $this->getEntityRepository()->findBy([
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

    /**
     * @param JobConfiguration $jobConfiguration
     * @param ConfigurationValues $values
     *
     * @return bool
     */
    private function hasLabelChange(JobConfiguration $jobConfiguration, ConfigurationValues $values)
    {
        if ($values->hasEmptyLabel()) {
            return false;
        }

        return $jobConfiguration->getLabel() != $values->getLabel();
    }

    /**
     * @param JobConfiguration $jobConfiguration
     * @return bool
     */
    public function owns(JobConfiguration $jobConfiguration)
    {
        $user = $this->tokenStorage->getToken()->getUser();

        if (is_null($jobConfiguration->getUser())) {
            return false;
        }

        if ($jobConfiguration->getUser()->equals($user)) {
            return true;
        }

        if (!$this->teamService->hasForUser($user)) {
            return false;
        }

        $people = $this->teamService->getPeopleForUser($user);

        foreach ($people as $person) {
            if ($person->equals($user)) {
                return true;
            }
        }

        return false;
    }
}
