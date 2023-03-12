# Migrating from version 2 to 3

More image formats and options are now available in version 3, however, if you would like to simply continue using
the module as you were before, then there are some changes that you will need to consider.

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

## Method visibility

Most internal methods in `ResponsiveImageExtension` have been set to `private`. If you have a use case for extending
this extension, then please let us know and we can look to update some methods to `protected`.

## New configuration method

In version 3, we support one responsive image set having multiple different source configurations (often referred to as
"art direction").

For example, in version 1 and 2, we could only do this:

```html
<picture>
    <source media="(min-width: 1200px) 800w" srcset="/assets/Uploads/Fill800-my-image.jpeg">
    <source media="(min-width: 800px) 400w" srcset="/assets/Uploads/Fill400-my-image.jpeg">
    <source media="(min-width: 200px) 100w" srcset="/assets/Uploads/Fill100-my-image.jpeg">
    <img src="/assets/Uploads/_resampled/Fill800x600-my-image.jpeg" />
</picture>
```

In version 3, we can now do something like this:

```html
<picture>
    <source
        media="(min-width: 992px)"
        sizes="33vw"
        srcset="/assets/Uploads/Fill900-my-image.jpg 900w,
                /assets/Uploads/Fill600-my-image.jpg 600w,
                /assets/Uploads/Fill400-my-image.jpg 400w"
   />
    <source
        media="(min-width: 768px)"
        sizes="50vw"
        srcset="/assets/Uploads/Fill500-my-image.jpg 500w,
                /assets/Uploads/Fill500-my-image.jpg 400w"
    />
    <source
        srcset="/assets/Uploads/Fill1550-my-image.jpg 1550w,
                /assets/Uploads/Fill775-my-image.jpg 775w,
                /assets/Uploads/Fill300-my-image.jpg 300w"
    />
    <img src="Fill900x600-my-image.jpg" class="class-one class-two" />
</picture>
```

See [Extended media query example (art direction)](../../README.md#extended-media-query-example--art-direction-) for an
explanation of this example.
