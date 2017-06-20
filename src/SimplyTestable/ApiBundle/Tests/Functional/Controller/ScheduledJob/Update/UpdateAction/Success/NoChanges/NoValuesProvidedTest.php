<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction\Success\NoChanges;

class NoValuesProvidedTest extends NoChangesTest {

    protected function getRequestPostData() {
        return [];
    }
}