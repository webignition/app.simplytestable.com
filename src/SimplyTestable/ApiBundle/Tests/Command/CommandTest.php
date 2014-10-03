<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

abstract class CommandTest extends ConsoleCommandTestCase {

    /**
     *
     * @return string
     */
    protected function getCommandName() {
        $excludedParts = [
            'ApiBundle',
            'Tests',
            'Command'
        ];
        $classNameParts = [];

        $rawClassNameParts = explode('\\', get_class($this));
        array_pop($rawClassNameParts);

        foreach ($rawClassNameParts as $classNamePart) {
            if (!in_array($classNamePart, $excludedParts)) {
                $classNameParts[] = strtolower($classNamePart);
            }
        }

        $classNameParts[count($classNameParts) - 1] = str_replace('command', '', $classNameParts[count($classNameParts) - 1]);

        return implode(':', $classNameParts);
    }

}
