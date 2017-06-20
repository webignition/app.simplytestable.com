<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command;

use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;

abstract class CommandTest extends ConsoleCommandTestCase {

    /**
     *
     * @return string
     */
    protected function getCommandName() {
        $excludedParts = [
            'ApiBundle',
            'Tests',
            'Functional',
            'Command'
        ];

        $rawClassNameParts = explode('\\', get_class($this));
        $classNameParts = [];
        $commandPartFound = false;

        foreach ($rawClassNameParts as $classNamePart) {
            if ($commandPartFound) {
                continue;
            }

            if (preg_match('/^.+Command$/', $classNamePart)) {
                $commandPartFound = true;
                $classNamePart = str_replace('Command', '', $classNamePart);
            }

            if (!in_array($classNamePart, $excludedParts)) {
                $classNameParts[] = strtolower($classNamePart);
            }
        }

        return implode(':', $classNameParts);
    }

}
