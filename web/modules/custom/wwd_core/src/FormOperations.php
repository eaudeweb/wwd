<?php

namespace Drupal\wwd_core;

use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Defines a class for form API hooks.
 *
 * @internal
 */
class FormOperations implements ContainerInjectionInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Cache manager.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $staticCache;

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FormOperations object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(
    AccountProxyInterface $account,
    CacheBackendInterface $cache,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->currentUser = $account;
    $this->staticCache = $cache;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('cache.default'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Form alterations.
   *
   * @see hook_form_alter()
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    $form_object = $form_state->getFormObject();
    $isAnonymous = $this->currentUser->isAnonymous();
    // Perform some form alterations for content entities forms.
    if (is_a($form_object, ContentEntityForm::class)) {
      $node = $form_object->getEntity();
      // Remove access for some fields for anonymous users.
      if ($isAnonymous) {
        $form['revision_information']['#access'] = FALSE;
        $form['actions']['preview']['#access'] = FALSE;
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
        $nodeStorage = $this->entityTypeManager->getStorage('node');
        $options = &drupal_static(__FUNCTION__);
        if (is_null($options)) {
          $cid = 'wwd:message:year';
          $data = $this->staticCache->get($cid);
          if (!$data) {
            $options = [];
            $options[''] = new TranslatableMarkup('- All -');
            $query = $nodeStorage->getQuery();
            $query->condition('type', 'message')
              ->condition('status', 1)
              ->sort('field_message_date', 'ASC');
            $result = $query->execute();
            if ($result) {
              $nodes = $nodeStorage->loadMultiple($result);
              foreach ($nodes as $node) {
                $date = $node->get('field_message_date')->value;
                $date = new DrupalDateTime($date, new \DateTimeZone('UTC'));
                $year = $date->format('Y');
                if (!isset($options[$year])) {
                  $options[$year] = $year;
                }
              }
            }
            $cache_tags = ['wwd:message:year'];
            $this->staticCache->set($cid, $options, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
          }
          else {
            $options = $data->data;
          }
        }
        $form['message_year'] = [
          '#title' => NULL,
          '#type' => 'select',
          '#options' => $options,
          '#size' => NULL,
          '#default_value' => NULL,
        ];
      }
    }
  }

  /**
   * Custom submit callback for events node form.
   */
  public function eventNodeSubmit($form, FormStateInterface $form_state) {
    if ($this->currentUser->isAnonymous()) {
      $this->messenger()->addStatus($this->t('Thank you for registering a new event.'));
      $form_state->setRedirect('<front>');
    }
  }

}
