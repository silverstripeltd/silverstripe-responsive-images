<?php

namespace Heyday\ResponsiveImages\Tests\Objects;

use Heyday\ResponsiveImages\Objects\ResponsiveImage;
use Heyday\ResponsiveImages\Objects\Source;
use Heyday\ResponsiveImages\Objects\SourceSet;
use ReflectionProperty;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

class ResponsiveImageTest extends SapphireTest
{
    protected static $fixture_file = 'ResponsiveImageTest.yml'; // phpcs:ignore

    public function testConstruct(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $format = ResponsiveImage::FORMAT_IMG;
        $defaultImageArguments = [
            [800, 800],
            [400, 400],
        ];
        $defaultImageMethod = 'Fill';
        $cssClasses = 'class-one class-two';

        $responsiveImage = ResponsiveImage::create(
            $image,
            $format,
            $defaultImageArguments,
            $defaultImageMethod,
            $cssClasses
        );

        $formatProperty = new ReflectionProperty($responsiveImage, 'format');
        $formatProperty->setAccessible(true);
        $argumentSetsProperty = new ReflectionProperty($responsiveImage, 'defaultImageArguments');
        $argumentSetsProperty->setAccessible(true);
        $methodProperty = new ReflectionProperty($responsiveImage, 'defaultImageMethod');
        $methodProperty->setAccessible(true);
        $cssProperty = new ReflectionProperty($responsiveImage, 'cssClasses');
        $methodProperty->setAccessible(true);

        $this->assertEquals($format, $formatProperty->getValue($responsiveImage));
        $this->assertEqualsCanonicalizing($defaultImageArguments, $argumentSetsProperty->getValue($responsiveImage));
        $this->assertEquals($defaultImageMethod, $methodProperty->getValue($responsiveImage));
        $this->assertEquals($cssClasses, $cssProperty->getValue($responsiveImage));
    }

    public function testGetFormat(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $format = ResponsiveImage::FORMAT_IMG;

        $responsiveImage = ResponsiveImage::create($image, $format);

        $this->assertEquals($format, $responsiveImage->getFormat());
    }

    public function testSetSourceGetSource(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $source = Source::create($image, [], 'Fill');
        $sourceSet = SourceSet::create();

        $responsiveImage = ResponsiveImage::create($image);
        $responsiveImage->setSource($source);

        $this->assertInstanceOf(Source::class, $responsiveImage->getSource());

        $responsiveImage->setSource($sourceSet);

        $this->assertInstanceOf(SourceSet::class, $responsiveImage->getSource());
    }

    public function testGetSourceIsIterable(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $source = Source::create($image, [], 'Fill');
        $sourceSet = SourceSet::create();

        $responsiveImage = ResponsiveImage::create($image);
        $responsiveImage->setSource($source);

        $this->assertFalse($responsiveImage->getSourceIsIterable());

        $responsiveImage->setSource($sourceSet);

        $this->assertTrue($responsiveImage->getSourceIsIterable());
    }

    public function testSetCssClassesCSSClasses(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $cssClasses = 'class-one class-two';
        $expectedClasses = sprintf('ResponsiveImage %s', $cssClasses);

        $responsiveImage = ResponsiveImage::create($image, ResponsiveImage::FORMAT_IMG);
        $responsiveImage->setCssClasses($cssClasses);
        // Remove any double ups of spaces (there can be some from coming ViewableData:CSSClasses)
        $resultingCss = preg_replace('/\s+/', ' ', $responsiveImage->CSSClasses());

        $this->assertEquals($expectedClasses, $resultingCss);
    }

    public function testSetDefaultImageArguments(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $defaultImageArguments = [
            [800, 800],
            [400, 400],
        ];

        $responsiveImage = ResponsiveImage::create($image, ResponsiveImage::FORMAT_IMG);
        $responsiveImage->setDefaultImageArguments($defaultImageArguments);

        $argumentSetsProperty = new ReflectionProperty($responsiveImage, 'defaultImageArguments');
        $argumentSetsProperty->setAccessible(true);

        $this->assertEqualsCanonicalizing($defaultImageArguments, $argumentSetsProperty->getValue($responsiveImage));
        $this->assertEqualsCanonicalizing($defaultImageArguments, $responsiveImage->getDefaultImageArguments());
    }

    public function testSetDefaultImageMethod(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $defaultImageMethod = 'Fill';

        $responsiveImage = ResponsiveImage::create($image, ResponsiveImage::FORMAT_IMG);
        $responsiveImage->setDefaultImageMethod($defaultImageMethod);

        $methodProperty = new ReflectionProperty($responsiveImage, 'defaultImageMethod');
        $methodProperty->setAccessible(true);

        $this->assertEquals($defaultImageMethod, $methodProperty->getValue($responsiveImage));
        $this->assertEquals($defaultImageMethod, $responsiveImage->getDefaultImageMethod());
    }

    public function testGetDefaultImageArgumentsConfig(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $expectedArguments = Config::inst()->get(ResponsiveImage::class, 'default_image_arguments');

        $responsiveImage = ResponsiveImage::create($image, ResponsiveImage::FORMAT_IMG);

        $this->assertEqualsCanonicalizing($expectedArguments, $responsiveImage->getDefaultImageArguments());
    }

    public function testGetDefaultImageMethodConfig(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $expectedMethod = Config::inst()->get(ResponsiveImage::class, 'default_method');

        $responsiveImage = ResponsiveImage::create($image, ResponsiveImage::FORMAT_IMG);

        $this->assertEquals($expectedMethod, $responsiveImage->getDefaultImageMethod());
    }

    public function testSetTemplate(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $template = 'Test/Template';

        $responsiveImage = ResponsiveImage::create($image, ResponsiveImage::FORMAT_IMG);
        $responsiveImage->setTemplate($template);

        $templateProperty = new ReflectionProperty($responsiveImage, 'template');
        $templateProperty->setAccessible(true);

        $this->assertEquals($template, $templateProperty->getValue($responsiveImage));
    }
}
