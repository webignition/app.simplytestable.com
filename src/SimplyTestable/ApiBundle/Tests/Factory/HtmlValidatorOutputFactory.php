<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use Symfony\Component\Yaml\Yaml;

class HtmlValidatorOutputFactory
{
    const DEFAULT_MESSAGES_DATA_RELATIVE_PATH  = '/../Fixtures/Data/HtmlValidatorOutputMessages/messages.yml';

    const KEY_MESSAGE_LAST_LINE = 'lastLine';
    const KEY_MESSAGE_LAST_COLUMN = 'lastColumn';
    const KEY_MESSAGE_MESSAGE = 'message';
    const KEY_MESSAGE_MESSAGE_ID = 'messageid';
    const KEY_MESSAGE_TYPE = 'type';
    const KEY_MESSAGE_INDEX = 'message-index';

    const DEFAULT_MESSAGE_LAST_LINE = 0;
    const DEFAULT_MESSAGE_LAST_COLUMN = 0;
    const DEFAULT_MESSAGE_ID = 'html5';
    const DEFAULT_MESSAGE_TYPE = 'error';

    /**
     * @var array
     */
    private $defaultMessageValues = [
        self::KEY_MESSAGE_LAST_LINE => self::DEFAULT_MESSAGE_LAST_LINE,
        self::KEY_MESSAGE_LAST_COLUMN => self::DEFAULT_MESSAGE_LAST_COLUMN,
        self::KEY_MESSAGE_MESSAGE_ID => self::DEFAULT_MESSAGE_ID,
        self::KEY_MESSAGE_TYPE => self::DEFAULT_MESSAGE_TYPE,
    ];

    /**
     * @var string[]
     */
    private $defaultMessages = [];

    public function __construct()
    {
        $messagesPath = realpath(__DIR__ . self::DEFAULT_MESSAGES_DATA_RELATIVE_PATH);
        $this->defaultMessages = Yaml::parse($messagesPath);
    }

    public function create($messageValuesCollection)
    {
        $outputMessages = [];

        foreach ($messageValuesCollection as $messageValues) {
            $currentMessageValues = array_merge($this->defaultMessageValues, $messageValues);

            if (isset($currentMessageValues[self::KEY_MESSAGE_INDEX])) {
                $defaultMessageIndex = $currentMessageValues[self::KEY_MESSAGE_INDEX];
                $currentMessageValues[self::KEY_MESSAGE_MESSAGE] = $this->defaultMessages[$defaultMessageIndex];
            }

            $outputMessages[] = [
                self::KEY_MESSAGE_LAST_LINE => $currentMessageValues[self::KEY_MESSAGE_LAST_LINE],
                self::KEY_MESSAGE_LAST_COLUMN => $currentMessageValues[self::KEY_MESSAGE_LAST_COLUMN],
                self::KEY_MESSAGE_MESSAGE => $currentMessageValues[self::KEY_MESSAGE_MESSAGE],
                self::KEY_MESSAGE_MESSAGE_ID => $currentMessageValues[self::KEY_MESSAGE_MESSAGE_ID],
                self::KEY_MESSAGE_TYPE => $currentMessageValues[self::KEY_MESSAGE_TYPE],
            ];
        }

        return [
            'messages' => $outputMessages,
        ];
    }
}
