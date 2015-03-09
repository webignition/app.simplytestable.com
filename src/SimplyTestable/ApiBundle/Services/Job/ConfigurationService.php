<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\EntityService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;

class ConfigurationService extends EntityService {

    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\Configuration';

    /**
     * @var TeamService
     */
    private $teamService;


    /**
     * @var User
     */
    private $user;

    /**
     * @param EntityManager $entityManager
     * @param TeamService $teamService
     */
    public function __construct(EntityManager $entityManager,TeamService $teamService) {
        parent::__construct($entityManager);
        $this->teamService = $teamService;
    }

    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }


    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user) {
        $this->user = $user;
        return $this;
    }


    /**
     * @param ConfigurationValues $values
     * @return JobConfiguration
     * @throws \SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception
     */
    public function create(ConfigurationValues $values) {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

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
        $jobConfiguration->setUser($this->user);
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
    public function get($label) {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        return $this->getEntityRepository()->findOneBy([
            'label' => $label,
            'user' => ($this->teamService->hasForUser($this->user)) ? $this->teamService->getPeopleForUser($this->user) : [$this->user]
        ]);
    }


    /**
     * @param JobConfiguration $jobConfiguration
     * @param ConfigurationValues $newValues
     * @return JobConfiguration
     * @throws \SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception
     */
    public function update(JobConfiguration $jobConfiguration, ConfigurationValues $newValues) {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        if ($newValues->hasNonEmptyLabel()) {
            if ($this->has($newValues->getLabel())) {
                throw new JobConfigurationServiceException(
                    'Label "' . $newValues->getLabel() . '" is not unique',
                    JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
                );
            }
        }

        if (!$newValues->hasWebsite()) {
            $newValues->setWebsite($jobConfiguration->getWebsite());
        }

        if (!$newValues->hasType()) {
            $newValues->setType($jobConfiguration->getType());
        }

        if (!$newValues->hasTaskConfigurationCollection()) {
            $newValues->setTaskConfigurationCollection($jobConfiguration->getTaskConfigurationsAsCollection());
        }

        if (!$newValues->hasParameters()) {
            $newValues->setParameters($jobConfiguration->getParameters());
        }

        if ($this->hasExisting($newValues)) {
            throw new JobConfigurationServiceException(
                'Matching configuration already exists',
                JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
            );
        }

        $jobConfiguration->setLabel($newValues->getLabel());
        $jobConfiguration->setUser($this->user);
        $jobConfiguration->setWebsite($newValues->getWebsite());
        $jobConfiguration->setType($newValues->getType());
        $jobConfiguration->setParameters($newValues->getParameters());

        foreach ($jobConfiguration->getTaskConfigurations() as $oldTaskConfiguration) {
            $this->getManager()->remove($oldTaskConfiguration);
        }

        $jobConfiguration->getTaskConfigurations()->clear();

        foreach ($newValues->getTaskConfigurationCollection()->get() as $taskConfiguration) {
            /* @var $taskConfiguration TaskConfiguration */
            $taskConfiguration->setJobConfiguration($jobConfiguration);
            $jobConfiguration->addTaskConfiguration($taskConfiguration);;
            $this->getManager()->persist($taskConfiguration);
        }

        $this->getManager()->persist($jobConfiguration);
        $this->getManager()->flush();

        return $jobConfiguration;
    }


    public function delete($label) {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

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

        $this->getManager()->flush();
    }

    /**
     * @return JobConfiguration[]
     * @throws \SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception
     */
    public function getList() {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        return $this->getEntityRepository()->findBy([
            'user' => ($this->teamService->hasForUser($this->user)) ? $this->teamService->getPeopleForUser($this->user) : [$this->user]
        ]);
    }


    /**
     * @throws \SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception
     */
    public function removeAll() {
        if (!$this->hasUser()) {
            throw new JobConfigurationServiceException(
                'User is not set',
                JobConfigurationServiceException::CODE_USER_NOT_SET
            );
        }

        if ($this->teamService->hasForUser($this->user)) {
            throw new JobConfigurationServiceException(
                'Unable to remove all; user is in a team',
                JobConfigurationServiceException::CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM
            );
        }

        $userJobConfigurations = $this->getEntityRepository()->findBy([
            'user' => $this->user
        ]);

        foreach ($userJobConfigurations as $userJobConfiguration) {
            /* @var $userJobConfiguration JobConfiguration */
            foreach ($userJobConfiguration->getTaskConfigurations() as $jobTaskConfiguration) {
                $this->getManager()->remove($jobTaskConfiguration);
            }

            $this->getManager()->remove($userJobConfiguration);
            $this->getManager()->flush($userJobConfiguration);
        }
    }


    /**
     * @param $label
     * @return bool
     */
    private function has($label) {
        return !is_null($this->get($label));
    }


    /**
     * @param ConfigurationValues $values
     * @return bool
     */
    private function hasExisting(ConfigurationValues $values) {
        $jobConfigurations = $this->getEntityRepository()->findBy([
            'website' => $values->getWebsite(),
            'type' => $values->getType(),
            'parameters' => $values->getParameters(),
            'user' => ($this->teamService->hasForUser($this->user)) ? $this->teamService->getPeopleForUser($this->user) : [$this->user]
        ]);

        foreach ($jobConfigurations as $jobConfiguration) {
            /* @var $jobConfiguration JobConfiguration */
            if ($values->getTaskConfigurationCollection()->equals($jobConfiguration->getTaskConfigurationsAsCollection())) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return bool
     */
    private function hasUser() {
        return $this->user instanceof User;
    }



}