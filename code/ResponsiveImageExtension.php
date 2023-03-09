<?php

namespace Heyday\ResponsiveImages;

use Exception;
use Heyday\ResponsiveImages\Objects\ResponsiveImage;
use Heyday\ResponsiveImages\Objects\Source;
use Heyday\ResponsiveImages\Objects\SourceSet;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\View\ViewableData;

/**
 * An extension to the Image class to inject methods for responsive image sets.
 * Image sets are defined in the config layer, e.g:
 *
 * Heyday\ResponsiveImages\ResponsiveImageExtension:
 *   sets:
 *     MyResponsiveImageSet:
 *       method: Fill
 *       arguments:
 *         "(min-width: 200px)": [200, 100]
 *         "(min-width: 800px)": [200, 400]
 *         "(min-width: 1200px) and (min-device-pixel-ratio: 2.0)": [800, 400]
 *       default_image_dimensions: [200, 400]
 *
 * This provides $MyImage.MyResponsiveImageSet to the template. For more
 * documentation on implementation, see the README file.
 *
 * @property DBFile|Image|$this $owner
 */
class ResponsiveImageExtension extends Extension
{
    public const FORMAT_IMG = 'img';
    public const FORMAT_PICTURE = 'picture';

    private const FORMATS = [
        self::FORMAT_IMG,
        self::FORMAT_PICTURE,
    ];

    private static array $default_image_dimensions = [800, 600];

    private static string $default_format = self::FORMAT_PICTURE;

    private static string $default_method = 'ScaleWidth';

    private static string $default_css_classes = '';

    /**
     * A wildcard method for handling responsive sets as template functions,
     * e.g. $MyImage.ResponsiveSet1
     *
     * @param string $setName The method called
     * @param array $args The arguments passed to the method
     */
    public function __call(string $setName, array $args): ?ViewableData
    {
        return $this->createResponsiveSet($setName, $args);
    }

    /**
     * Defines all the methods that can be called in this class.
     */
    public function allMethodNames(): array
    {
        return array_map('strtolower', array_keys($this->getSets()));
    }

    protected function getSets(): array
    {
        return Config::inst()->get(static::class, 'sets') ?? [];
    }

    /**
     * Sends the required HTML structure to the template for a responsive image
     *
     * @param string $setName The name of the image set to be used
     * @param array $defaultImageArgs The arguments passed to the responsive image method call to be used for the
     *  default image: e.g. $MyImage.ResponsiveSet(800, 600) or $MyImage.ResponsiveSet('Fill', 800, 600)
     */
    protected function createResponsiveSet(string $setName, array $defaultImageArgs): ?ViewableData
    {
        // Find the associated config by set name
        $config = $this->getConfigForSet($setName);

        // If there is no matching config, then we can't proceed
        if (!$config) {
            throw new Exception(sprintf('Unable to find set matching "%s"', $setName));
        }

        // There are three ways that a responsive image can be configured

        // A single definition, which can be used as either img or picture
        $singleDefinition = $config['definition'] ?? null;
        // Art direction means multiple source tags, and so can only be supported by picture
        $artDirection = $config['art_direction'] ?? null;
        // Legacy support (which is effectively "art direction")
        $arguments = $config['arguments'] ?? null;

        // If no valid configuration was supplied, then we can't proceed
        if (!is_array($singleDefinition) && !is_array($artDirection) && !is_array($arguments)) {
            throw new Exception(sprintf(
                'Responsive set "%s" has no "definition", "art_direction", or "arguments" defined in its config',
                $setName
            ));
        }

        if (is_array($singleDefinition)) {
            // Single definitions can use either the img or picture format
            $format = $config['format'] ?? Config::inst()->get(static::class, 'default_format');
            // There is no manipulation of data for us to do here
            $definition = $singleDefinition;

            // Make sure a valid format was specified
            if (!in_array($format, self::FORMATS)) {
                throw new Exception(sprintf(
                    'Invalid format "%s" specified for set "%s"',
                    $format,
                    $setName
                ));
            }
        } else if (is_array($artDirection)) {
            // Only "picture" format is supported for art direction, as it requires multiple source tags
            $format = self::FORMAT_PICTURE;
            // Art direction can be provided as an array of setNames and/or an array of full config definitions. Here
            // we will convert all of those into an array of standard definitions
            $definition = $this->getDefinitionForArtDirection($setName, $artDirection);
        } else {
            // Only "picture" format is supported for art direction
            $format = self::FORMAT_PICTURE;
            // Legacy format provides the cropping method as part of the outer config layer
            $method = $config['method'] ?? Config::inst()->get(static::class, 'default_method');
            // Legacy support for old configuration. This is effectively turning the original configuration method into
            // "art direction"
            $definition = $this->getDefinitionForArguments($setName, $arguments, $method);
        }

        // Instantiate our new ResponsiveImage
        $responsiveImage = new ResponsiveImage($this->owner, $format);

        // An associative array indicates a single level of definitions, so we only need one Source (and not a
        // SourceSet - which is an ArrayList of Source)
        $source = $this->hasAssociativeArray($definition)
            ? $this->getSource($setName, $definition)
            : $this->getSourceSet($setName, $definition);

        // Use specified CSS classes, or fall back to default classes if none are provided
        $cssClasses = $config['css_classes'] ?? Config::inst()->get(static::class, 'default_css_classes');

        // If arguments were passed to this method e.g. $MyImage.ResponsiveSet(800, 600) or
        // $MyImage.ResponsiveSet('Fill', 800, 600), then we will use those for our default image size
        // Otherwise, we will attempt to fall back to any configured size, and failing that we'll use our default
        $defaultImageDimensions = $this->getDimensionsFromDefaultArgs($defaultImageArgs)
            ?? $config['default_image']
            ?? Config::inst()->get(static::class, 'default_image_dimensions');

        // Prioritise using the method that was used when calling this method
        // e.g. $MyImage.ResponsiveSet('Fill', 800, 600)
        // Otherwise, we will attempt to fall back to any configured method, and failing that we'll use our default
        $defaultImageMethod = $this->getMethodFromDefaultArgs($defaultImageArgs)
            ?? $config['default_image_method']
            ?? Config::inst()->get(static::class, 'default_method');

        $responsiveImage->setSource($source);
        $responsiveImage->setDefaultImageDimensions($defaultImageDimensions);
        $responsiveImage->setDefaultImageMethod($defaultImageMethod);
        $responsiveImage->setCssClasses($cssClasses);

        return $responsiveImage;
    }

    private function getDefinitionForArguments(string $setName, array $arguments, string $method): array
    {
        $definition = [];

        foreach ($arguments as $query => $args) {
            if (is_numeric($query) || !$query) {
                throw new Exception(sprintf(
                    'Responsive set %s has an empty media query. Please check your config format',
                    $setName
                ));
            }

            if (!is_array($args) || empty($args)) {
                throw new Exception(sprintf(
                    "Responsive set %s doesn't have any arguments provided for the query: %s",
                    $setName,
                    $query
                ));
            }

            $definition[] = [
                'method' => $method,
                'media' => [
                    $query,
                ],
                'dimension_sets' => [
                    $args,
                ]
            ];
        }

        return $definition;
    }

    /**
     * Return a resampled image equivalent to $Image.MethodName(...$args) in a template
     */
    protected function getResampledImage(string $methodName, array $args): DBFile|Image|null
    {
        return call_user_func_array([$this->owner, $methodName], $args);
    }

    /**
     * Due to {@link Object::allMethodNames()} requiring methods to be expressed
     * in all lowercase, getting the config for a given method requires a
     * case-insensitive comparison.
     *
     * @param string $setName The name of the responsive image set to get
     */
    protected function getConfigForSet(string $setName): ?array
    {
        $name = strtolower($setName);
        $sets = array_change_key_case($this->getSets());

        return $sets[$name] ?? null;
    }

    private function getMethodFromDefaultArgs(array $defaultArgs): ?string
    {
        // Passing a method as part of default args is only valid if there are 2 or 3 args provided
        if (count($defaultArgs) < 2) {
            return null;
        }

        // Method must be provided as the first param, and it is always a string
        if (!is_string($defaultArgs[0])) {
            return null;
        }

        // First param is a string, so it must be a method name
        return $defaultArgs[0];
    }

    private function getDimensionsFromDefaultArgs(array $defaultArgs): ?array
    {
        // If there are no default args, then there are no dimension
        if (!$defaultArgs) {
            return null;
        }

        // The first arg can also be a string, for the requested format, if it isn't, then we can just return the
        // array as is
        if (!is_string($defaultArgs[0])) {
            return $defaultArgs;
        }

        // Remove the first array item, because it must be a format
        array_shift($defaultArgs);

        return $defaultArgs;
    }

    private function getDefinitionForArtDirection(string $setName, array $artDirection): array
    {
        $definition = [];

        // We support developers providing the name of another configuration, or providing the configuration as an
        // array
        foreach ($artDirection as $setNameOrDefinition) {
            if (!is_array($setNameOrDefinition) && !is_string($setNameOrDefinition)) {
                throw new Exception(sprintf(
                    'Invalid art_direction values provided for set "%s", only string or array allowed',
                    $setName
                ));
            }

            // If the value is an array, then we assume a full definition was provided
            if (is_array($setNameOrDefinition)) {
                $definition[] = $setNameOrDefinition;

                continue;
            }

            // If the value
            $config = $this->getConfigForSet($setNameOrDefinition);

            if (!$config) {
                throw new Exception(sprintf('Unable to find set matching "%s"', $setNameOrDefinition));
            }

            $singleDefinition = $config['definition'] ?? null;

            if (!is_array($singleDefinition)) {
                throw new Exception(sprintf(
                    'Responsive set "%s" has no "definition" defined in its config',
                    $setNameOrDefinition
                ));
            }

            $definition[] = $singleDefinition;
        }

        return $definition;
    }

    private function getSourceSet(string $setName, array $definitionSets)
    {
        $sourceSet = new SourceSet();

        foreach ($definitionSets as $definition) {
            $sourceSet->push($this->getSource($setName, $definition));
        }

        return $sourceSet;
    }

    private function getSource(string $setName, array $definition): Source
    {
        $dimensionSets = $definition['dimension_sets'] ?? null;

        if (!is_array($dimensionSets)) {
            throw new Exception(sprintf(
                'The "definition" configuration for set "%s" does not have any sources defined',
                $setName
            ));
        }

        $method = $config['method'] ?? Config::inst()->get(static::class, 'default_method');

        if (!$this->owner->hasMethod($method)) {
            throw new Exception(sprintf(
                '%s has no method %s',
                get_class($this->owner),
                $method
            ));
        }

        $source = new Source($this->owner, $dimensionSets, $method);
        $source->setSizes($definition['sizes'] ?? []);
        $source->setMedia($definition['media'] ?? []);

        return $source;
    }

    private function hasAssociativeArray(array $definitions): bool
    {
        if (!$definitions) {
            return false;
        }

        return array_keys($definitions) !== range(0, count($definitions) - 1);
    }
}
