<?php

namespace Drupal\wwd_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines WwdSettings form class.
 */
class WwdSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('wwd_core.settings');

    $form['nominatim'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Nominatim Geocoder Provider'),
    ];
    $form['nominatim']['nominatim_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nominatim Base URL Override'),
      '#default_value' => $config->get('nominatim_base_url') ?? NULL,
      '#description' => $this->t('Override URL base to query a custom Nominatim server.'),
    ];

    $form['nominatim']['nominatim_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Custom Email for Nominatim Requests'),
      '#default_value' => $config->get('nominatim_email') ?? NULL,
      '#description' => $this->t('If you are making large numbers of request please include a valid email address. This information will be used to contact you in the event of a problem. Defaults to the site email address.'),
    ];

    $form['mapbox'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mapbox settings'),
    ];

    $form['mapbox']['mapbox_style'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mapbox Style URL'),
      '#default_value' => $config->get('mapbox_style') ?? NULL,
      '#description' => $this->t('Copy and paste the style URL. Example: %url.', [
        '%url' => 'mapbox://styles/johndoe/erl4zrwto008ob3f2ijepsbszg',
      ]),
    ];

    $form['mapbox']['mapbox_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map access token'),
      '#default_value' => $config->get('mapbox_token') ?? NULL,
      '#description' => $this->t('You will find this in the Mapbox user account settings'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wwd_core_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wwd_core.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('wwd_core.settings');
    $config->set('nominatim_base_url', rtrim($form_state->getValue('nominatim_base_url'), '/'));
    $config->set('nominatim_email', $form_state->getValue('nominatim_email'));
    $config->set('mapbox_style', $form_state->getValue('mapbox_style'));
    $config->set('mapbox_token', $form_state->getValue('mapbox_token'));
    $config->save();
  }

}
