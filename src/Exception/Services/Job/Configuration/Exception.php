<?php

namespace App\Exception\Services\Job\Configuration;

class Exception extends \Exception
{

    const CODE_USER_NOT_SET = 1;
    const CODE_LABEL_NOT_UNIQUE = 2;
    const CODE_CONFIGURATION_ALREADY_EXISTS = 3;
    const CODE_NO_SUCH_CONFIGURATION = 4;
    const CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY = 5;
    const CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM = 6;
    const CODE_LABEL_CANNOT_BE_EMPTY = 7;
    const CODE_WEBSITE_CANNOT_BE_EMPTY = 8;
    const CODE_TYPE_CANNOT_BE_EMPTY = 9;

    public function isUserNotSetException(): bool
    {
        return $this->getCode() === self::CODE_USER_NOT_SET;
    }

    public function isLabelNotUniqueException(): bool
    {
        return $this->getCode() === self::CODE_LABEL_NOT_UNIQUE;
    }

    public function isConfigurationAlreadyExistsException(): bool
    {
        return $this->getCode() == self::CODE_CONFIGURATION_ALREADY_EXISTS;
    }

    public function isNoSuchConfigurationException(): bool
    {
        return $this->getCode() == self::CODE_NO_SUCH_CONFIGURATION;
    }

    public function isTaskConfigurationCollectionIsEmptyException(): bool
    {
        return $this->getCode() == self::CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY;
    }

    public function isUnableToPerformAsUserIsInATeamException(): bool
    {
        return $this->getCode() == self::CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM;
    }

    public function isLabelCannotBeEmptyException(): bool
    {
        return $this->getCode() == self::CODE_LABEL_CANNOT_BE_EMPTY;
    }

    public function isWebsiteCannotBeEmptyException(): bool
    {
        return $this->getCode() == self::CODE_WEBSITE_CANNOT_BE_EMPTY;
    }

    public function isTypeCannotBeEmptyException(): bool
    {
        return $this->getCode() == self::CODE_TYPE_CANNOT_BE_EMPTY;
    }
}
