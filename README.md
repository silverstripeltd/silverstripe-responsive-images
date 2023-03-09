# Responsive Images for SilverStripe

## Introduction

This module provides the ability to send a series of configured image sizes to the client without actually loading any
resources until a media query can be executed.

This is particularly useful for sites that use responsive design, because it means that smaller viewports can receive
images optimised for their size rather than pulling down a single image optimised for desktop.

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

### Simple media query example



### Fluid image example

Below is an example of a basic "fluid" responsive image.

* We have defined the maximum width that we would like the image to be viewed at any particular break point (e.g. at
  1200px or greater, do not display the image any larger than 33% of the view width)
* We have defined the available image sizes that we have (1800w, 900w, 600w)

The result is that the browser can then pick which sized image will fit the available space (based on the limitations)
we set in our `sizes`.

```html
<!-- Example using config format 'img' -->
<img
    class="ResponsiveImage class-one class-two"
    srcset="/assets/Uploads/auckland__ScaleWidthWzE4MDBd.jpg 1800w,
            /assets/Uploads/auckland__ScaleWidthWzkwMF0.jpg 900w,
            /assets/Uploads/auckland__ScaleWidthWzYwMF0.jpg 600w"
    sizes="(min-width: 1200px) 33vw,
           (min-width: 800px) 50vw,
           100vw"
    src="/assets/Uploads/auckland__FocusFillWyItMC42MSIsIjAuMDEiLDgwMCw2MDBd.jpg"
/>

<!-- Example using config format 'picture' -->
<picture>
    <source
        srcset="/assets/Uploads/auckland__ScaleWidthWzE4MDBd.jpg 1800w,
                /assets/Uploads/auckland__ScaleWidthWzkwMF0.jpg 900w,
                /assets/Uploads/auckland__ScaleWidthWzYwMF0.jpg 600w"
        sizes="(min-width: 1200px) 33vw,
               (min-width: 800px) 50vw,
               100vw"
    >
    <img
        src="/assets/Uploads/auckland__FocusFillWyItMC42MSIsIjAuMDEiLDgwMCw2MDBd.jpg"
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


```yml
---
After: 'silverstripe-responsive-images/*'
---
Heyday\ResponsiveImages\ResponsiveImageExtension:
    sets:
        ResponsiveSet1:
            css_classes: classname
            arguments:
                '(min-width: 1200px)': [ 800 ]
                '(min-width: 800px)': [ 400 ]
                '(min-width: 200px)': [ 100 ]
    
        ResponsiveSet2:
            template: Includes/MyCustomImageTemplate
            method: Fill
            arguments:
                '(min-width: 1000px) and (min-device-pixel-ratio: 2.0)': [ 1800, 1800 ]
                '(min-width: 1000px)': [ 900, 900 ]
                '(min-width: 800px) and (min-device-pixel-ratio: 2.0)': [ 1400, 1400 ]
                '(min-width: 800px)': [ 700, 700 ]
                '(min-width: 400px) and (min-device-pixel-ratio: 2.0)': [ 600, 600 ]
                '(min-width: 400px)': [ 300, 300 ]
            default_arguments: [ 1200, 1200 ]
    
        ResponsiveSet3:
            method: Pad
            arguments:
                '(min-width: 800px)': [ 700, 700, '666666' ]
                '(min-width: 400px)': [ 300, 300, '666666' ]
            default_arguments: [ 1200, 1200, '666666' ]
```

Now, run `?flush=1` to refresh the config manifest, and you will have the new methods injected into your Image class
that you can use in templates.

```
$MyImage.ResponsiveSet1
$MyImage.ResponsiveSet2
$MyImage.ResponsiveSet3
```

The output of the first method (`ResponsiveSet1`) will look something like this, remember that the first matching
media-query will be taken:

```html

<picture>
    <source media="(min-width: 1200px) 800w" srcset="/assets/Uploads/_resampled/SetWidth100-my-image.jpeg">

    <source media="(min-width: 800px) 400w" srcset="/assets/Uploads/_resampled/SetWidth400-my-image.jpeg">

    <source media="(min-width: 200px) 100w" srcset="/assets/Uploads/_resampled/SetWidth100-my-image.jpeg">

    <img src="/assets/Uploads/_resampled/SetWidth640480-my-image.jpeg" alt="my-image.jpeg">
</picture>
```

### Other options

Each set should have a "default_arguments" property set in case the browser does not support media queries. By default,
the "default_arguments" property results in an 800x600 image, but this can be overridden in the config.

```yml
Heyday\ResponsiveImages\ResponsiveImageExtension:
  default_arguments: [ 1200, 768 ]
```

You can also pass arguments for the default image at the template level.

```
$MyImage.MyResponsiveSet(900, 600)
```

The default resampling method is SetWidth, but this can be overridden in your config.

```yml
Heyday\ResponsiveImages\ResponsiveImageExtension:
    default_method: Fill
```

It can also be passed into your template function.

```
$MyImage.MyResponsiveSet('Fill', 800, 600)
```
