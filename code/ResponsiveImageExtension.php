<?php

namespace Heyday\ResponsiveImages;

use Exception;
use RuntimeException;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
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
 *       default_arguments: [200, 400]
 *
 * This provides $MyImage.MyResponsiveImageSet to the template. For more
 * documentation on implementation, see the README file.
 */
class ResponsiveImageExtension extends Extension
{
    private static array $default_arguments = [800, 600];

    private static string $default_method = 'ScaleWidth';

    private static string $default_css_classes = '';

    /**
     * A wildcard method for handling responsive sets as template functions,
     * e.g. $MyImage.ResponsiveSet1
     *
     * @param string $method The method called
     * @param array $args The arguments passed to the method
     */
    public function __call(string $method, array $args): ?ViewableData
    {
        $config = $this->getConfigForSet($method);

        if (!$config) {
            return null;
        }

        return $this->createResponsiveSet($config, $args, $method);
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
     * Requires the necessary JS and sends the required HTML structure to the
     * template for a responsive image set.
     *
     * @param array $config The configuration of the responsive image set
     * @param array $defaultArgs The arguments passed to the responsive image
     *                           method call, e.g. $MyImage.ResponsiveSet(800x600)
     * @param string $set The method, or responsive image set, to generate
     */
    protected function createResponsiveSet(array $config, array $defaultArgs, string $set): ViewableData
    {
        if (!isset($config['arguments']) || !is_array($config['arguments'])) {
            throw new Exception(sprintf(
                'Responsive set %s does not have any arguments defined in its config.',
                $set
            ));
        }

        if (empty($defaultArgs)) {
            if (isset($config['default_arguments'])) {
                $defaultArgs = $config['default_arguments'];
            } else {
                $defaultArgs = Config::inst()->get(static::class, 'default_arguments');
            }
        }

        if (isset($config['method'])) {
            $methodName = $config['method'];
        } else {
            $methodName = Config::inst()->get(static::class, 'default_method');
        }

        if (!$this->owner->hasMethod($methodName)) {
            throw new RuntimeException(sprintf(
                '%s has no method %s',
                get_class($this->owner),
                $methodName
            ));
        }

        // Create the resampled images for each query in the set
        $sizes = ArrayList::create();

        foreach ($config['arguments'] as $query => $args) {
            if (is_numeric($query) || !$query) {
                throw new Exception(sprintf(
                    'Responsive set %s has an empty media query. Please check your config format',
                    $set
                ));
            }

            if (!is_array($args) || empty($args)) {
                throw new Exception(sprintf(
                    "Responsive set %s doesn't have any arguments provided for the query: %s",
                    $set,
                    $query
                ));
            }

            $sizes->push(ArrayData::create([
                'Image' => $this->getResampledImage($methodName, $args),
                'Query' => $query,
            ]));
        }

        // Use specified classes, or fall back to default classes if none are provided
        $extraClasses = $config['css_classes'] ?? Config::inst()->get(static::class, 'default_css_classes');
        // Use specified template, or fall back to default template
        $templatePath = $config['template'] ?? 'Includes/ResponsiveImageSet';

        return $this->owner
            ->customise([
                'Sizes' => $sizes,
                'ExtraClasses' => $extraClasses,
                'DefaultImage' => $this->getResampledImage($methodName, $defaultArgs),
            ])
            ->renderWith($templatePath);
    }

    /**
     * Return a resampled image equivalent to $Image.MethodName(...$args) in a template
     */
    protected function getResampledImage(string $methodName, array $args): DBFile|Image
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
}
