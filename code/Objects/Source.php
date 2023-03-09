<?php

namespace Heyday\ResponsiveImages\Objects;

use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

class Source extends ViewableData
{
    use Injectable;

    public function __construct(
        private readonly DBFile|Image $image,
        private readonly array $argumentSets,
        private readonly string $method,
        private array $sizes = [],
        private array $media = []
    ) {
        parent::__construct();
    }

    public function setSizes(array $sizes): void
    {
        $this->sizes = $sizes;
    }

    public function setMedia(array $media): void
    {
        $this->media = $media;
    }

    public function getSources(): ArrayList
    {
        $sources = ArrayList::create();

        foreach ($this->argumentSets as $argumentSet) {
            $sources->push($this->getResampledImage($this->method, $argumentSet));
        }

        return $sources;
    }

    public function getSizesAsString(): string
    {
        return implode(', ', $this->sizes);
    }

    public function getMediaAsString(): string
    {
        return implode(', ', $this->media);
    }

    public function forTemplate(): ViewableData
    {
        return $this->renderWith('Hayday/ResponsiveImages/Objects/Source');
    }

    /**
     * Return a resampled image equivalent to $Image.MethodName(...$args) in a template
     */
    private function getResampledImage(string $methodName, array $args): DBFile|Image|null
    {
        return call_user_func_array([$this->image, $methodName], $args);
    }
}
