<?php

namespace App\Exception;

class JobMutationException extends \Exception
{
    const CODE_START_DATE_TIME_ALREADY_SET = 1;
    const MESSAGE_START_DATE_TIME_ALREADY_SET = 'Cannot set start datetime once previously set';

    const CODE_START_DATE_TIME_NOT_SET = 2;
    const MESSAGE_START_DATE_TIME_NOT_SET = 'Cannot set end datetime without first setting start datetime';

    public static function createStartDateTimeAlreadySetException()
    {
        return new JobMutationException(
            self::MESSAGE_START_DATE_TIME_ALREADY_SET,
            self::CODE_START_DATE_TIME_ALREADY_SET
        );
    }

    public static function createStartDateTimeNotSetException()
    {
        return new JobMutationException(
            self::MESSAGE_START_DATE_TIME_NOT_SET,
            self::CODE_START_DATE_TIME_NOT_SET
        );
    }
}
