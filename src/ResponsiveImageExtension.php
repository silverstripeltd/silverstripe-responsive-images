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
 *       default_image_arguments: [200, 400]
 *
 * This provides $MyImage.MyResponsiveImageSet to the template. For more
 * documentation on implementation, see the README file.
 *
 * @property DBFile|Image|$this $owner
 */
class ResponsiveImageExtension extends Extension
{
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

    private function getSets(): array
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
    private function createResponsiveSet(string $setName, array $defaultImageArgs): ?ViewableData
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
        // Legacy support for simple "media query" picture sources (which is effectively "art direction")
        $arguments = $config['arguments'] ?? null;
        // Only the Legacy method provides method at the "top level"
        $method = null;

        // If no valid configuration was supplied, then we can't proceed
        if (!is_array($singleDefinition) && !is_array($arguments)) {
            throw new Exception(sprintf(
                'Responsive set "%s" has no "definition" or "arguments" defined in its config',
                $setName
            ));
        }

        // TODO this needs a refactor. Just trying to get it working atm
        if (is_array($singleDefinition)) {
            if ($this->containsArtDirection($singleDefinition)) {
                // Only "picture" format is supported for art direction, as it requires multiple source tags
                $format = ResponsiveImage::FORMAT_PICTURE;
                // Art direction can be provided as an array of setNames and/or an array of full config definitions. Here
                // we will convert all of those into an array of standard definitions
                $definition = $this->getDefinitionForArtDirection($setName, $singleDefinition);
            } else {
                // Single definitions can use either the img or picture format
                $format = $config['format'] ?? Config::inst()->get(static::class, 'default_format');
                // There is no manipulation of data for us to do here
                $definition = $singleDefinition;

                // Make sure a valid format was specified
                if (!in_array($format, ResponsiveImage::FORMATS)) {
                    throw new Exception(sprintf(
                        'Invalid format "%s" specified for set "%s"',
                        $format,
                        $setName
                    ));
                }
            }
        } else {
            // Only "picture" format is supported for art direction
            $format = ResponsiveImage::FORMAT_PICTURE;
            // Legacy format provides the cropping method as part of the outer config layer
            $method = $config['method'] ?? null;
            // Legacy support for simple media configuration. This is effectively turning the original configuration
            // method into "art direction"
            $definition = $this->getDefinitionForArguments($setName, $arguments, $method);
        }

        // Instantiate our new ResponsiveImage
        $responsiveImage = ResponsiveImage::create($this->owner, $format);

        // An associative array indicates a single level of definitions, so we only need one Source (and not a
        // SourceSet - which is an ArrayList of Source)
        $source = $this->hasAssociativeArray($definition)
            ? $this->getSourceForDefinition($setName, $definition)
            : $this->getSourceSetForDefinitionSets($setName, $definition);

        // Add specified CSS classes (if any were provided)
        $cssClasses = $config['css_classes'] ?? null;

        // If arguments were passed to this method e.g. $MyImage.ResponsiveSet(800, 600) or
        // $MyImage.ResponsiveSet('Fill', 800, 600), then we will use those for our default image size
        $defaultImageArguments = $this->getDimensionsFromDefaultArgs($defaultImageArgs)
            // We can try to fall back to any configured arguments
            ?? $config['default_image_arguments']
            // Otherwise nothing, and ResponsiveImage will fall back to global default
            ?? null;

        // Prioritise using the method that was used when calling this method
        // e.g. $MyImage.ResponsiveSet('Fill', 800, 600)
        $defaultImageMethod = $this->getMethodFromDefaultArgs($defaultImageArgs)
            // We can try to fall back to any configured method
            ?? $config['default_image_method']
            // If using the Legacy format, then method might have been provided here
            ?? $method
            // Otherwise nothing, and ResponsiveImage will fall back to global default
            ?? null;

        // You can define a template that you would like to use for your particular configuration. The default
        // is defined in ResponsiveImage
        $template = $config['template'] ?? null;

        $responsiveImage->setSource($source);
        $responsiveImage->setDefaultImageArguments($defaultImageArguments);
        $responsiveImage->setDefaultImageMethod($defaultImageMethod);
        $responsiveImage->setCssClasses($cssClasses);
        $responsiveImage->setTemplate($template);

        return $responsiveImage;
    }

    private function getDefinitionForArguments(string $setName, array $arguments, ?string $method): array
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
                'argument_sets' => [
                    $args,
                ]
            ];
        }

        return $definition;
    }

    /**
     * Due to {@link Object::allMethodNames()} requiring methods to be expressed
     * in all lowercase, getting the config for a given method requires a
     * case-insensitive comparison.
     *
     * @param string $setName The name of the responsive image set to get
     */
    private function getConfigForSet(string $setName): ?array
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
                    'Invalid definition values provided for set "%s", only string or array allowed',
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

    private function getSourceSetForDefinitionSets(string $setName, array $definitionSets)
    {
        $sourceSet = SourceSet::create();

        foreach ($definitionSets as $definition) {
            $sourceSet->push($this->getSourceForDefinition($setName, $definition));
        }

        return $sourceSet;
    }

    private function getSourceForDefinition(string $setName, array $definition): Source
    {
        // The argument sets are sets of dimensions (and any other args that any image manipulation might support)
        $argumentSets = $definition['argument_sets'] ?? null;

        if (!is_array($argumentSets)) {
            throw new Exception(sprintf(
                'The "definition" configuration for set "%s" does not have any "argument_sets" defined',
                $setName
            ));
        }

        $method = $config['method'] ?? Config::inst()->get(ResponsiveImage::class, 'default_method');

        if (!$this->owner->hasMethod($method)) {
            throw new Exception(sprintf(
                '%s has no method %s',
                get_class($this->owner),
                $method
            ));
        }

        $sizes = $definition['sizes'] ?? null;

        // We allow the config to be a string if there is only one size, but we want to handle this as an array
        // from this point forward
        if (is_string($sizes)) {
            $sizes = [$sizes];
        }

        $media = $definition['media'] ?? null;

        // We allow the config to be a string if there is only one media, but we want to handle this as an array
        // from this point forward
        if (is_string($media)) {
            $media = [$media];
        }

        $source = Source::create($this->owner, $argumentSets, $method);
        $source->setSizes($sizes);
        $source->setMedia($media);

        return $source;
    }

    private function containsArtDirection(array $definition): bool
    {
        if (array_key_exists('method', $definition)) {
            return false;
        }

        if (array_key_exists('argument_sets', $definition)) {
            return false;
        }

        if (array_key_exists('media', $definition)) {
            return false;
        }

        if (array_key_exists('sizes', $definition)) {
            return false;
        }

        return true;
    }

    private function hasAssociativeArray(array $definitions): bool
    {
        if (!$definitions) {
            return false;
        }

        return array_keys($definitions) !== range(0, count($definitions) - 1);
    }
}
