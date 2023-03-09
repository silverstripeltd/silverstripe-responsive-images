# Migrating from version 2 to 3

More image formats and options are now available in version 3, however, if you would like to simply continue using
the module as you were before, then there are some changes that you will need to consider.

## Method visibility

Most internal methods in `ResponsiveImageExtension` have been set to `private`. If you have a use case for extending
this extension, then please let us know and we can look to update some methods to `protected`.

## Default configurations have moved

`ResponsiveImage` now holds the global default configurations.

Available configuration are below, and these are used any time no specific configuration has been provided to the
set (or through method arguments):

* `default_format`: (New) The default format (`img` or `picture`) that we will use to generate your responsive images.
* `default_method`: The image manipulation method (e.g. "Fill") that we will use to generate each image source.
* `default_image_arguments`: Previously called `default_arguments`. The default arguments that we'll use to generate
  the default image for a set.
* `default_css_classes`: The default CSS classes that we will apply to an image set.

**Before:**

```yml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  default_arguments: [ 1200, 768 ]
  default_method: Fill
  default_css_classes: 'class-one class-two'
```

**After:**

```yml
Heyday\ResponsiveImages\Objects\ResponsiveImage:
  default_image_arguments: [ 1200, 768 ]
  default_method: Fill
  default_css_classes: 'class-one class-two'
```

## `default_arguments`

The configuration for `default_arguments` has been renamed to `default_image_arguments` so that it is more descriptive
about what it controls, and so that it is more differentiated from the `method` and `arguments` configurations, as
`default_arguments` is in no way a "default" or "fall back" for any of the image source sets that we define.

**Before:**

```yaml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  sets:
    ResponsiveSet1:
      arguments:
        '(min-width: 1200px)': [ 800 ]
        '(min-width: 800px)': [ 400 ]
        '(min-width: 200px)': [ 100 ]
      default_arguments: [ 1200, 1200 ]
```

**After:**

```yaml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  sets:
    ResponsiveSet1:
      arguments:
        '(min-width: 1200px)': [ 800 ]
        '(min-width: 800px)': [ 400 ]
        '(min-width: 200px)': [ 100 ]
      default_image_arguments: [ 1200, 1200 ]
```
