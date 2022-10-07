<?php

/**
 * @file
 * Eau de Web Theme theme file.
 * 
 * Demo description with code and link
 *  '#description' => t('Demo code <code>.theme-colors</code> and link @demo_link.', [
 *    '@demo_link' => Link::fromTextAndUrl('Containers',
 *                    Url::fromUri('https://getbootstrap.com/docs/5.0/layout/containers/', [
 *                      'absolute' => TRUE,
 *                      'fragment' => 'containers'
 *                     ]))->toString(),
 *  ]),
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function edwt_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
  if (isset($form_id)) {
    return;
  }

  $options_theme = [
    'none' => 'do not apply theme',
    'light' => 'light (dark text/links against a light background)',
    'dark' => 'dark (light/white text/links against a dark background)',
  ];

  $options_container = [
    'container' => 'Fixed',
    'container-fluid' => 'Fluid',
  ];

  $form['edwt'] = [
    '#type' => 'vertical_tabs',
    '#title' => t('Theme settings'),
    // '#prefix' => '<div class="h2">' . t('Some text before title') . '</div>',
    '#weight' => -10,
  ];

  // Main settings
  $form['settings'] = array(
    '#type'         => 'details',
    '#title'        => t('Main'),
    //'#description'  => t('some description'),
    '#group' => 'edwt',
    '#weight' => 1,
  );
    include 'theme-settings/settings-global.inc';

    // Sections settings
  $form['sections'] = array(
    '#type'         => 'details',
    '#title'        => t('Sections'),
    '#group' => 'edwt',
    '#weight' => 2,
  );
    include 'theme-settings/settings-sections.inc';

  // Style settings
  $form['style'] = array(
    '#type'         => 'details',
    '#title'        => t('Style'),
    '#description'  => t('Style colors, sizes etc'),
    '#group' => 'edwt',
    '#weight' => 3,
  );
    include 'theme-settings/settings-style.inc';

// In progress: Easy config inline form.
  // $form['form'] = array(
  //   '#type'         => 'details',
  //   '#title'        => t('Forms'),
  //   '#group' => 'edwt',
  //   '#weight' => 4,
  // );
  //   include 'theme-settings/settings-forms.inc';
}
