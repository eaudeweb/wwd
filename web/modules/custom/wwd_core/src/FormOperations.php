<?php

namespace Drupal\wwd_core;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\wwd_core\Services\EventsManager;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Defines a class for form API hooks.
 *
 * @internal
 */
class FormOperations implements ContainerInjectionInterface {

  use MessengerTrait;
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Events manager.
   *
   * @var \Drupal\wwd_core\Services\EventsManager
   */
  protected $eventsManager;

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new FormOperations object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user.
   * @param \Drupal\wwd_core\Services\EventsManager $events_manager
   *   Events manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(
    AccountProxyInterface $account,
    EventsManager $events_manager,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager
  ) {
    $this->currentUser = $account;
    $this->eventsManager = $events_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('wwd_core.events_manager'),
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Form alterations.
   *
   * @see hook_form_alter()
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    $form_object = $form_state->getFormObject();
    $contentSettings = $this->eventsManager
      ->getSettings('wwd_core.content_settings');
    $isAnonymous = $this->currentUser->isAnonymous();
    // Perform some form alterations for content entities forms.
    if (is_a($form_object, ContentEntityForm::class)) {
      $node = $form_object->getEntity();
      // Remove access for some fields for anonymous users.
      if ($isAnonymous) {
        $form['revision_information']['#access'] = FALSE;
        $form['actions']['preview']['#access'] = FALSE;
        $form['langcode']["#access"] = FALSE;
        $form['actions']['submit']['#value'] = $this->t('Register');
      }

      // Custom redirect for anonymous users for events form.
      if ($node instanceof Node && $node->bundle() == 'events') {
        foreach (array_keys($form['actions']) as $action) {
          if ($action != 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
            $form['actions'][$action]['#submit'][] = [
              $this,
              'eventNodeSubmit',
            ];
          }
        }
        // Remove link to event description for anonymous users.
        if ($isAnonymous) {
          $form['field_link']['widget'][0]['uri']['#description'] = NULL;
        }
      }
    }
    // Exposed form alterations for views.
    if ($form_id == 'views_exposed_form') {
      // Adjust message date exposed filter.
      if (isset($form['#id']) && $form['#id'] == 'views-exposed-form-messages-page') {
        $options = $this->eventsManager->getEventYearOptions();
        $defaultYear = $contentSettings->get('events.default_filter') ?? '';
        $form['message_year'] = [
          '#title' => NULL,
          '#type' => 'select',
          '#options' => $options,
          '#size' => NULL,
          '#default_value' => FALSE,
        ];
        $existingFilter = $form_state->getUserInput();
        if (empty($existingFilter['message_year'])) {
          $form_state->setUserInput(['message_year' => $defaultYear]);
        }
      }
    }

    // Alter node multimedia form.
    if (strpos($form_id, 'node_multimedia') !== FALSE) {
      // Hide logo fields unless logo type is selected.
      $fieldsToHide = [
        'field_ai_logo',
        'field_eps_logo',
        'field_image',
      ];
      foreach ($fieldsToHide as $fieldName) {
        $form[$fieldName]['#states'] = [
          'visible' => [
            ':input[name="field_type"]' => ['value' => 'logo'],
          ],
        ];
      }

    }
  }

  /**
   * Custom submit callback for events node form.
   */
  public function eventNodeSubmit($form, FormStateInterface $form_state) {
    if ($this->currentUser->isAnonymous()) {
      $language = $this->languageManager->getCurrentLanguage()->getId();
      $url = Url::fromUserInput('/' . $language . '/event-registration-confirmation');
      return $form_state->setRedirectUrl($url);
    }
  }

}
