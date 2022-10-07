# Info
Theme name: World WildLife Day
Theme machine name: wwd

## Abbreviations
#### themeMachineName.settings.yml
tsg - Theme settings global
ts - Theme settings

# World WildLife Day theme
## INTRODUCTION
Some description.

## FEATURES
* Drupal 8 and 9 compatible
* Can be used as is (subtheme is required for template overrides)
* Bootstrap 5 library
* Bootstrap 5 breakpoints
* Bootstrap 5 configuration within admin user interface
<!-- * Bootstrap 5 style guide -->
<!-- * Bootstrap 5 integration with CKEditor -->

## REQUIREMENTS
[NPM](https://nodejs.org/en/)

### Installation: composer
INSTALLATION

<!-- `composer require drupal/edwt` -->
Head to `Appearance` and install edwt theme.

## CONFIGURATION
Head to `Appearance` and clicking edwt `settings`.

## Development and patching
- Install development dependencies by running `npm install`
- To compile SASS and JS (minified version for live environment ) run `npm run build`
- To compile postcss.css (for developers will compile each time you change the
.sass, .js and .twig files ) run `npm run watch`
<!-- - To lint SASS files run `npm run lint:sass` (it will fail build if lint
fails) -->
<!-- - To lint JS files run `npm run lint:js` (it will fail build if lint
fails) -->


## FAQ
### FAQ - Hidden markdown examples
Link: [current documentation](https://getbootstrap.com/docs/5.0/components/dropdowns/#menu-items).

List:
* List 1
* List 2

Inline code `some code here`

Code section
```
/**
 * @file
 * Description.
*/

(function ($, Drupal) {
  Drupal.behaviors.general = {
    attach: function (context, settings) {
      // script.
    }
  };
});
```
