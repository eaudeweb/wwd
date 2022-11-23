<?php

namespace Drupal\wwd_core\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\wwd_core\Services\EventsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines WwdContentSettings form class.
 */
class WwdContentSettings extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Events manager.
   *
   * @var \Drupal\wwd_core\Services\EventsManager
   */
  protected $eventsManager;

  /**
   * Constructor for WwdContentSettings class.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\wwd_core\Services\EventsManager $events_manager
   *   Events manager.
   */
  public function __construct(
    EntityTypeManager $entity_type_manager,
    EventsManager $events_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventsManager = $events_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('wwd_core.events_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('wwd_core.content_settings');

    $form['homepage'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Home page settings'),
      '#tree' => TRUE,
    ];
    // Get default event.
    $nodeOption = NULL;
    $nodeId = $config->get('homepage.upcoming_event') ?? NULL;
    if (!empty($nodeId)) {
      $node = $this->entityTypeManager->getStorage('node')
        ->load($nodeId);
      $nodeOption = $node instanceof Node ? $node : NULL;
    }

    $form['homepage']['upcoming_event'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Upcoming Event'),
      '#description' => $this->t('Upcoming event shown on homepage.'),
      '#selection_handler' => 'default:upcoming_events',
      '#selection_settings' => [
        'target_bundles' => ['events'],
        'filter' => [
          'status' => 1,
          'upcoming' => TRUE,
        ],
      ],
      '#default_value' => $nodeOption,
    ];

    $form['events'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Events page settings'),
      '#tree' => TRUE,
    ];
    $form['events']['default_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Predefined Year Filter'),
      '#options' => $this->eventsManager->getEventYearOptions(),
      '#default_value' => $config->get('events.default_filter') ?? date('Y'),
      '#description' => $this->t('Predefined year filter on events listing page.'),
    ];

    $form['messages'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Messages settings'),
      '#tree' => TRUE,
    ];
    $form['messages']['previous_messages_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous Messages Button Text'),
      '#default_value' => $config->get('messages.previous_messages_button') ?? 'Previous Messages',
      '#description' => $this->t('Change "Previous Messages Button" text from every individual message page (e.g /message/john-smith).'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wwd_core_content_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wwd_core.content_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('wwd_core.content_settings');
    parent::submitForm($form, $form_state);

    $values = $form_state->cleanValues()->getValues();
    foreach ($values as $key => $value) {
      $config
        ->set($key, $value)
        ->save();
    }
  }

}
