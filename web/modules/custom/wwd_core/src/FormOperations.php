<?php

namespace Drupal\wwd_core;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
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
   * Constructs a new FormOperations object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user.
   */
  public function __construct(
    AccountProxyInterface $account
  ) {
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
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
