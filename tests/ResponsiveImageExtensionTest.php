<?php

namespace Heyday\ResponsiveImages\Tests;

use Heyday\ResponsiveImages\Objects\ResponsiveImage;
use Heyday\ResponsiveImages\ResponsiveImageExtension;
use ReflectionMethod;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class ResponsiveImageExtensionTest extends SapphireTest
{
    public function testHasAssociativeArray(): void
    {
        $extension = Injector::inst()->get(ResponsiveImageExtension::class);
        $method = new ReflectionMethod($extension, 'hasAssociativeArray');

        // Empty array
        $arrayOne = [];
        // Array of arrays
        $arrayTwo = [
            [],
            [],
        ];
        // One or more associative items
        $arrayThree = [
            [],
            'Test' => '',
        ];

        $this->assertFalse($method->invoke($extension, $arrayOne));
        $this->assertFalse($method->invoke($extension, $arrayTwo));
        $this->assertTrue($method->invoke($extension, $arrayThree));
    }

    protected function setUp(): void
    {
        parent::setUp();

        ResponsiveImage::config()->set(
            'sets',
            [

            ]
        );
    }
}
