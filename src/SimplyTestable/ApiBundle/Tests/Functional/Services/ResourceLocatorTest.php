<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

class ResourceLocatorTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider locateDataProvider
     *
     * @param string $name
     * @param string $expectedRelativeResourcePath
     */
    public function testLocate($name, $expectedRelativeResourcePath)
    {
        $resourceLocator = $this->container->get('simplytestable.services.resourcelocator');

        $expectedAbsoluteResourcePath = preg_replace(
            '/app$/',
            '',
            $this->container->get('kernel')->getRootDir()
        ) . $expectedRelativeResourcePath;

        $this->assertEquals($expectedAbsoluteResourcePath, $resourceLocator->locate($name));
    }

    /**
     * @return array
     */
    public function locateDataProvider()
    {
        return [
            'application state' => [
                'name' => '@SimplyTestableApiBundle/Resources/config/state/test',
                'expectedRelativeResourcePath' =>
                    'src/SimplyTestable/ApiBundle/Resources/config/state/test',
            ],
        ];
    }
}
