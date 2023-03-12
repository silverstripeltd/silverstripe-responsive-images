# Responsive Images for SilverStripe

* [Introduction](#introduction)
  * [Migrating from version 2 to 3](docs/en/migrating-from-2-to-3.md)
* [Requirements](#requirements)
* [Installation](#installation)
* [How to use](#how-to-use)
* [Defining responsive image sets](#defining-responsive-image-sets)
  * [Global defaults](#global-defaults)
  * [Fluid image example](#fluid-image-example)
  * [Extended media query example (art direction)](#extended-media-query-example--art-direction-)
  * [Simple media query (`arguments`)](#simple-media-query--arguments-)

## Introduction

This module provides the ability to send a series of configured image sizes to the client without actually loading any
resources until a media query can be executed.

This is particularly useful for sites that use responsive design, because it means that smaller viewports can receive
images optimised for their size rather than pulling down a single image optimised for desktop.

### Migrating from version 2 to 3

[Migrating from version 2 to 3](docs/en/migrating-from-2-to-3.md)

## Requirements

* PHP ^8.1
* SilverStripe ^5.0

Legacy support:

* For a SS 4.x compatible-version, please see branch 2.0
* For a SS 3.x compatible-version, please see branch 1.0

## Installation

```bash
composer require heyday/silverstripe-responsive-images
```

## How to use

Once you have this module installed, you’ll need to configure named sets of image sizes in your site’s yaml config (
e.g. `mysite/_config/config.yml`).

Details on how to create your sets can be found below, but the general usage is as follows:

```yaml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  sets:
    ResponsiveSet1:
      ... definition
    ResponsiveSet2:
      ... definition
    ResponsiveSet3:
      ... definition
```

And in your template you will now be able to chain the "set name" from your Image object.

```silverstripe
$MyImage.ResponsiveSet1
$MyImage.ResponsiveSet2
$MyImage.ResponsiveSet3
```

You can also specify the CSS classes to be included with the image within the template or configuration
(configuration method explained later):

```silverstripe
$MyImage.ResponsiveSet1.Css('class-one class-two')
```

The default image (used for the `img` tag or `src` attribute) can also be defined through the template by providing
arguments to your set name method (or through configuration).

This will use the default image manipulation method, but set the dimensions:

```silverstripe
$MyImage.MyResponsiveSet(800, 600)
```

Or you can also provide an image manipulation method:

```silverstripe
$MyImage.MyResponsiveSet('Fill', 800, 600)
```

## Defining responsive image sets

### Global defaults

```yml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  # Options are 'img' or 'picture'. Note that 'img' is not available when multiple aspect ratios are required
  default_format: picture
  # The default image manipulation method we will use when creating any/all image variants if no method is specified
  # through the set configuration
  default_method: ScaleWidth
  # The default image arguments used when creating the default img source
  default_image_arguments: [800, 600]
  # CSS classes that will be added to the img tag if none are specified through config or template
  default_css_classes: ''
```

### Fluid image example

Below is an example of a basic "fluid" responsive image.

* We have defined the maximum width that we would like the image to be viewed at any particular break point (e.g. at
  1200px or greater, do not display the image any larger than 33% of the view width)
* We have defined the available image sizes that we have (1800w, 900w, 600w)

The result is that the browser can then pick which sized image will fit the available space (based on the limitations
we set in our `sizes`).

```html
<!-- Example using config format 'img' -->
<img
    class="ResponsiveImage class-one class-two"
    srcset="/assets/Uploads/Fill1800-my-image.jpg 1800w,
            /assets/Uploads/Fill900-my-image.jpg 900w,
            /assets/Uploads/Fill600-my-image.jpg 600w"
    sizes="(min-width: 1200px) 33vw,
           (min-width: 800px) 50vw,
           100vw"
    src="/assets/Uploads/Fill800x600-my-image.jpg"
/>

<!-- Example using config format 'picture' -->
<picture>
    <source
        srcset="/assets/Uploads/Fill1800-my-image.jpg 1800w,
                /assets/Uploads/Fill900-my-image.jpg 900w,
                /assets/Uploads/Fill600-my-image.jpg 600w"
        sizes="(min-width: 1200px) 33vw,
               (min-width: 800px) 50vw,
               100vw"
    >
    <img
        src="/assets/Uploads/Fill800x600-my-image.jpg"
        class="ResponsiveImage class-one class-two"
    >
</picture>
```

The configuration to create either of the above, is as follows:

```yaml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  sets:
    ResponsiveSet1:
      css_classes: 'class-one class-two'
      format: img # or 'picture'
      definition:
        method: Fill
        argument_sets:
          [1800, 1800]
          [900, 900]
          [600, 600]
        sizes:
          '(min-width: 1200px) 33vw'
          '(min-width: 800px) 50vw'
          '100vw'
```

### Extended media query example (art direction)

Now we'll get into a more complex setup, where we have multiple picture sources that we want to serve under different
`media` queries.

This is commonly known as "art direction", and it also allows you to swap between images of different aspect ratios.

Note: This is a similar output to what we achieve with [Simple media query example](#simple-media-query-example), but
this time we're making use of all 3 of the source attributes (`media`, `srcset`, and `sizes`).

**The goal is:**

* Default Image: My fallback will be a middle sized image (900 x 600)
* On desktop:
  * I want to display my images at a maximum width of 33% of the current viewport width.
  * I want to provide a few different images sizes that might be used between the smallest and largest desktop sizes
* On Tablet:
  * I want to display my images at a maximum width of 50% of the current viewport width.
  * I want to provide a few different images sizes that might be used between the smallest and largest tablet sizes
* On mobile (default):
  * I want to display my images at a maximum width of 100% of the current viewport width.
  * It is quite common for mobile devices to provide images that are `2x` (for high pixel density devices). This can
    be achieved simply by providing image that are 2x the pixel size. Browsers are now smart enough to determine
    this if they are high pixel density devices.
  * I also want a standard `1x` image, and a small image (3 total).

```silverstripe
<picture>
    <source
        <%-- Media query stating that this rule should be used at viewports 992px and greater (Desktop) --%>
        media="(min-width: 992px)"
        <%-- Size indicates the maximum size (or sizes) that we allow our image to be displayed --%>
        <%-- 33vw = 33% of the current viewport width --%>
        sizes="33vw"
        <%-- These are the images that the browser can choose from --%>
        <%-- The browser will always choose the smallest image that will fit in the space we have available --%>
        srcset="/assets/Uploads/Fill900-my-image.jpg 900w,
                /assets/Uploads/Fill600-my-image.jpg 600w,
                /assets/Uploads/Fill400-my-image.jpg 400w"
   />
    <source
        <%-- Media query stating that this rule should be used at viewports 768px and greater (Tablet) --%>
        media="(min-width: 768px)"
        <%-- Size indicates the maximum size (or sizes) that we allow our image to be displayed --%>
        <%-- 50vw = 50% of the current viewport width --%>
        sizes="50vw"
        <%-- These are the images that the browser can choose from --%>
        <%-- The browser will always choose the smallest image that will fit in the space we have available --%>
        srcset="/assets/Uploads/Fill500-my-image.jpg 500w,
                /assets/Uploads/Fill500-my-image.jpg 400w"
    />
    <source
        <%-- No media query, as this is the default / fall-back --%>
        <%-- There are 3 images available. The top is 2x, the middle is 1x, and the bottom is for low bandwidth or --%>
        <%-- for small devices --%>
        srcset="/assets/Uploads/Fill1550-my-image.jpg 1550w,
                /assets/Uploads/Fill775-my-image.jpg 775w,
                /assets/Uploads/Fill300-my-image.jpg 300w"
    />
    <%-- Our default image with CSS classes added --%>
    <img src="Fill900x600-my-image.jpg" class="class-one class-two" />
</picture>
```

The way we can achieve this configuration is effectively by duplicating the `definition` configuration that we 
previously made in [Fluid image example](#fluid-image-example).

The `definition` configuration can support an array of `definitions`. This array can be of other set names, and/or it
also allows you to provide full configuration arrays.

```yaml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  sets:
    # A reusable responsive set
    ResponsiveDesktop1:
      definition:
        method: Fill
        # We want this to apply to our desktop viewport size
        # This config can be provided as a string or array, here we'll just provide a string since there is only one
        media: '(min-width: 992px)'
        # The maximum size is 33% of the viewport width
        # This config can be provided as a string or array, here we'll just provide a string since there is only one
        sizes: '33vw'
        # And the available image sizes
        argument_sets:
          [900, 900]
          [600, 600]
          [400, 400]
    ResponsiveSet1:
      # No 'format' needs to be supplied, because "picture" is the only valid option for art direction
      definition:
          # This will include the definition from the set we defined above (ResponsiveDesktop1)
        - ResponsiveDesktop1
          # Each definition (like before) can have their own method for variant generation (because you might want to
          # crop different sources in different ways
        - method: ScaleWidth
          # We want this to apply to our tablet viewport size
          media: '(min-width: 768)'
          # The maximum size is 50% of the viewport width
          sizes: '50vw'
          # And the available image sizes
          argument_sets:
            [500, 500]
            [400, 400]
          # Using a different method for Mobile images (this one from the FocusPoint module)
        - method: FocusFill
          # And the available image sizes
          argument_sets:
            [1550, 1550]
            [775, 775]
            [300, 300]
```

### Simple media query (`arguments`)

The legacy method of defining sets is also still supported, but please note that the available configurations are
limited, and follow a slightly different format to the examples above.

If you would like to achive an output similar to below:

```html
<picture>
    <source media="(min-width: 1200px) 800w" srcset="/assets/Uploads/Fill800-my-image.jpeg">
    <source media="(min-width: 800px) 400w" srcset="/assets/Uploads/Fill400-my-image.jpeg">
    <source media="(min-width: 200px) 100w" srcset="/assets/Uploads/Fill100-my-image.jpeg">
    <img
        src="/assets/Uploads/_resampled/Fill800x600-my-image.jpeg"
        alt="my-image.jpeg"
        class="ResponsiveImage class-one class-two"
    />
</picture>
```

You could create some configurations as follows:

```yaml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  sets:
    ResponsiveSet1:
      # No 'method' config has been defined, so we would use the global default
      # No 'default_image_arguments' config have been provided, so we would use the global default ([800, 600])
      # css_classes can also be defined in our template, EG: $MyImage.ResponsiveSet1.Css('class-one class-two')
      css_classes: 'class-one class-two'
      # Key values here are used verbatim within the "media" attribute
      # The values of your array are passed as arguments to the image manipulation method you defined. These equate to
      # [ width, height ] (where height is optional)
      arguments:
        '(min-width: 1200px)': [ 800 ]
        '(min-width: 800px)': [ 400 ]
        '(min-width: 200px)': [ 100 ]
    ResponsiveSet2:
      # We will use this method when creating image variants. Note: This is used for all image sources
      method: Fill
      # We will use these arguments when generating the variant to be used as the "Default" image source
      default_image_arguments: [ 1200, 1200 ]
      arguments:
        '(min-width: 1000px) and (min-device-pixel-ratio: 2.0)': [ 1800, 1800 ]
        '(min-width: 1000px)': [ 900, 900 ]
        '(min-width: 800px) and (min-device-pixel-ratio: 2.0)': [ 1400, 1400 ]
        '(min-width: 800px)': [ 700, 700 ]
        '(min-width: 400px) and (min-device-pixel-ratio: 2.0)': [ 600, 600 ]
        '(min-width: 400px)': [ 300, 300 ]
```

In my template I can include this image (with CSS classes):

```silverstripe
$MyImage.ResponsiveSet1.Css('class-one class-two')
```
