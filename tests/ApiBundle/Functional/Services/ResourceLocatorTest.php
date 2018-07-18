<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Services\ResourceLocator;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

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
        $resourceLocator = self::$container->get(ResourceLocator::class);

        $expectedAbsoluteResourcePath = preg_replace(
            '/app$/',
            '',
            self::$container->get('kernel')->getRootDir()
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
