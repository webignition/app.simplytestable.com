<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HappyPath;

class FromRootSitemapTest extends HappyPathTest {

    protected function getFixtureMessages() {
        return array(
            $this->getDefaultRobotsTxtFixtureContent(),
            $this->getDefaultSitemapXmlFixtureContent()
        );
    }  

}
