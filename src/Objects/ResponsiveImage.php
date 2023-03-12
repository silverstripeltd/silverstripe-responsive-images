<?php

namespace Heyday\ResponsiveImages\Objects;

use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\View\ViewableData;

class ResponsiveImage extends ViewableData
{

    use Configurable;

    public const FORMAT_IMG = 'img';
    public const FORMAT_PICTURE = 'picture';

    public const FORMATS = [
        self::FORMAT_IMG,
        self::FORMAT_PICTURE,
    ];

    private static array $default_image_arguments = [800, 600];

    private static string $default_format = self::FORMAT_PICTURE;

    private static string $default_method = 'ScaleWidth';

    private static string $default_css_classes = '';

    private SourceSet|Source|null $source = null;

    private ?string $template = null;

    public function __construct(
        private readonly DBFile|Image $image,
        private readonly string $format = self::FORMAT_PICTURE,
        private ?array $defaultImageArguments = null,
        private ?string $defaultImageMethod = null,
        private ?string $cssClasses = null
    ) {
        parent::__construct();
    }

    public function setSource(Source|SourceSet|null $source): void
    {
        $this->source = $source;
    }

    public function setCssClasses(?string $cssClasses): void
    {
        $this->cssClasses = $cssClasses;
    }

    public function setDefaultImageArguments(?array $defaultImageArguments): void
    {
        $this->defaultImageArguments = $defaultImageArguments;
    }

    public function setDefaultImageMethod(?string $defaultImageMethod): void
    {
        $this->defaultImageMethod = $defaultImageMethod;
    }

    public function setTemplate(?string $template): void
    {
        $this->template = $template;
    }

    public function getDefaultImageArguments(): ?array
    {
        return $this->defaultImageArguments ?? Config::inst()->get(static::class, 'default_image_arguments');
    }

    public function getDefaultImageMethod(): ?string
    {
        return $this->defaultImageMethod ?? Config::inst()->get(static::class, 'default_method');
    }

    public function getSource(): Source|SourceSet|null
    {
        return $this->source;
    }

    public function getSourceIsIterable(): bool
    {
        return $this->getSource() instanceof SourceSet;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getDefaultImage(): DBFile|Image
    {
        return call_user_func_array([$this->image, $this->getDefaultImageMethod()], $this->getDefaultImageArguments());
    }

    /**
     * A chainable method that can be called from within a Silverstripe template
     * EG: $MyImage.ResponsiveSet1.Css('class-one class-two')
     */
    public function Css(?string $cssClasses): static
    {
        $this->setCssClasses($cssClasses);

        return $this;
    }

    /**
     * Enhance the default CSSClasses method provided by ViewableData
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
     */
    public function CSSClasses($stopAtClass = self::class)
    {
        $additionalClasses = $this->cssClasses
            ?? Config::inst()->get(static::class, 'default_css_classes')
            ?? null;

        return sprintf('%s %s', parent::CSSClasses(), $additionalClasses);
    }

    public function forTemplate(): ViewableData
    {
        if ($this->template) {
            return $this->renderWith($this->template);
        }

        return $this->renderWith('Hayday/ResponsiveImages/Objects/ResponsiveImage');
    }
}
