<?php

namespace Drupal\wwd_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'AddToAnyFollowBlock' Block.
 *
 * @Block(
 *   id = "add_to_any_follow_block",
 *   admin_label = @Translation("Social Follow Links"),
 *   category = @Translation("WWD"),
 * )
 */
class AddToAnyFollowBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Default links.
   *
   * @var array
   */
  const SOCIAL_LINKS = [
    'facebook',
    'twitter',
    'instagram',
    'youtube',
    'flickr',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [];
    $config = $this->getConfiguration();
    foreach (self::SOCIAL_LINKS as $link) {
      if (!empty($config[$link])) {
        $links[$link] = $config[$link];
      }
    }
    return [
      '#theme' => 'add_to_any_follow',
      '#links' => $links,
      '#cache' => [
        'contexts' => [
          'url',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form = parent::blockForm($form, $form_state);
    // Available options.
    $options = [
      'facebook' => 'WorldWildlifeDay',
      'twitter' => 'wildlifeday',
      'instagram' => 'worldwildlifeday',
      'youtube' => 'WorldWildlifeDay',
      'flickr' => 'worldwildlifeday',
    ];
    $config = $this->getConfiguration();
    foreach ($options as $option => $defaultValue) {
      $form[$option] = [
        '#type' => 'textfield',
        '#title' => $this->t('Provide profile ID for @option', [
          '@option' => ucfirst($option),
        ]),
        '#default_value' => $config[$option] ?? $defaultValue,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->cleanValues()->getValues();
    $options = self::SOCIAL_LINKS;
    foreach ($options as $option) {
      $this->configuration[$option] = $values[$option];
    }

  }

}
