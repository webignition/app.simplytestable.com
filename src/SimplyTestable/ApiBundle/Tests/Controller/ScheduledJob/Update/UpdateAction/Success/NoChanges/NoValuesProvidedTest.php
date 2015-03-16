<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Success\NoChanges;

class NoValuesProvidedTest extends NoChangesTest {

    protected function getRequestPostData() {
        return [];
    }
}