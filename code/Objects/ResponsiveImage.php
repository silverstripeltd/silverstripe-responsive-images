<?php

namespace Heyday\ResponsiveImages\Objects;

use Heyday\ResponsiveImages\ResponsiveImageExtension;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\ViewableData;

class ResponsiveImage extends ViewableData
{
    public const FORMAT_IMG = 'img';
    public const FORMAT_PICTURE = 'picture';

    private SourceSet|Source|null $source = null;

    public function __construct(
        private readonly DBFile|Image $image,
        private readonly string $format = self::FORMAT_PICTURE,
        private ?array $defaultImageDimensions = null,
        private ?string $defaultImageMethod = null,
        private ?string $cssClasses = null
    ) {
        parent::__construct();
    }

    public function getSource(): Source|SourceSet|null
    {
        return $this->source;
    }

    public function setSource(Source|SourceSet|null $source): void
    {
        $this->source = $source;
    }

    public function getSourceIsIterable(): bool
    {
        return $this->getSource() instanceof SourceSet;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setCssClasses(?string $cssClasses): void
    {
        $this->cssClasses = $cssClasses;
    }

    public function setDefaultImageDimensions(?array $defaultImageDimensions): void
    {
        $this->defaultImageDimensions = $defaultImageDimensions;
    }

    public function setDefaultImageMethod(?string $defaultImageMethod): void
    {
        $this->defaultImageMethod = $defaultImageMethod;
    }

    public function getDefaultImage(): DBFile|Image
    {
        $methodName = $this->defaultImageMethod ?? Config::inst()->get(ResponsiveImageExtension::class, 'default_method');
        $dimensions = $this->defaultImageDimensions ?? Config::inst()->get(ResponsiveImageExtension::class, 'default_arguments');

        return call_user_func_array([$this->image, $methodName], $dimensions);
    }

    public function CSSClasses($stopAtClass = self::class)
    {
        return sprintf('%s %s', parent::CSSClasses(), $this->cssClasses);
    }

    public function forTemplate(): ViewableData
    {
        return $this->renderWith('Hayday/ResponsiveImages/Objects/ResponsiveImage');
    }
}
