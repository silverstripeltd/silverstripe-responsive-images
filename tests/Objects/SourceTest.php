<?php

namespace Heyday\ResponsiveImages\Tests\Objects;

use Heyday\ResponsiveImages\Objects\Source;
use ReflectionProperty;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\SapphireTest;

class SourceTest extends SapphireTest
{
    protected static $fixture_file = 'SourceTest.yml'; // phpcs:ignore

    public function testConstruct(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $method = 'Fill';
        $argumentSets = [
            [800, 800],
            [400, 400],
        ];
        $sizes = [
            '33vw',
        ];
        $media = [
            '(min-width: 1200px)',
        ];

        $source = Source::create($image, $argumentSets, $method, $sizes, $media);

        $argumentSetsProperty = new ReflectionProperty($source, 'argumentSets');
        $argumentSetsProperty->setAccessible(true);
        $methodProperty = new ReflectionProperty($source, 'method');
        $methodProperty->setAccessible(true);
        $sizesProperty = new ReflectionProperty($source, 'sizes');
        $sizesProperty->setAccessible(true);
        $mediaProperty = new ReflectionProperty($source, 'media');
        $mediaProperty->setAccessible(true);

        $this->assertEqualsCanonicalizing($argumentSets, $argumentSetsProperty->getValue($source));
        $this->assertEquals($method, $methodProperty->getValue($source));
        $this->assertEqualsCanonicalizing($sizes, $sizesProperty->getValue($source));
        $this->assertEqualsCanonicalizing($media, $mediaProperty->getValue($source));
    }

    public function testSetSizes(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $method = 'Fill';
        $argumentSets = [];
        $sizes = [
            '33vw',
        ];

        $source = Source::create($image, $argumentSets, $method);
        $source->setSizes($sizes);

        $sizesProperty = new ReflectionProperty($source, 'sizes');
        $sizesProperty->setAccessible(true);

        $this->assertEqualsCanonicalizing($sizes, $sizesProperty->getValue($source));
    }

    public function testSetMedia(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $method = 'Fill';
        $argumentSets = [];
        $media = [
            '(min-width: 1200px)',
        ];

        $source = Source::create($image, $argumentSets, $method);
        $source->setMedia($media);

        $mediaProperty = new ReflectionProperty($source, 'media');
        $mediaProperty->setAccessible(true);

        $this->assertEqualsCanonicalizing($media, $mediaProperty->getValue($source));
    }

    public function testGetSizesAsString(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $method = 'Fill';
        $argumentSets = [];
        $sizes = [
            '(min-width: 1200px)',
            '(min-width: 600px)',
        ];

        $source = Source::create($image, $argumentSets, $method);
        $source->setSizes($sizes);

        $this->assertEquals('(min-width: 1200px), (min-width: 600px)', $source->getSizesAsString());
    }

    public function testGetMediaAsString(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $method = 'Fill';
        $argumentSets = [];
        $media = [
            '(min-width: 1200px)',
            '(min-width: 600px)',
        ];

        $source = Source::create($image, $argumentSets, $method);
        $source->setMedia($media);

        $this->assertEquals('(min-width: 1200px), (min-width: 600px)', $source->getMediaAsString());
    }

    public function testGetSources(): void
    {
        $image = $this->objFromFixture(Image::class, 'image1');
        $image->setFromLocalFile(dirname(__FILE__) . '/../resources/1200x800.png');
        $argumentSets = [
            [900, 600],
            [600, 400],
        ];

        $source = Source::create($image, $argumentSets, 'Fill');

        // Just a basic sanity check to make sure we get the correct number of sources. Some assumptions made that
        // the resampled image function works (since that's not really functionality of this module)
        $this->assertCount(2, $source->getSources());
    }
}
