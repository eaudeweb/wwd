<?php

namespace Drupal\wwd_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines NominatimSettings form class.
 */
class NominatimSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('wwd_core.nominatim_settings');

    $form['nominatim_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nominatim Base URL Override'),
      '#default_value' => $config->get('nominatim_base_url'),
      '#description' => $this->t('Override URL base to query a custom Nominatim server.'),
    ];

    $form['nominatim_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Custom Email for Nominatim Requests'),
      '#default_value' => $config->get('nominatim_email'),
      '#description' => $this->t('If you are making large numbers of request please include a valid email address. This information will be used to contact you in the event of a problem. Defaults to the site email address.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wwd_core_nominatim_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wwd_core.nominatim_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('wwd_core.nominatim_settings');
    $config->set('nominatim_base_url', rtrim($form_state->getValue('nominatim_base_url'), '/'));
    $config->set('nominatim_email', $form_state->getValue('nominatim_email'));

    $config->save();
  }

}
