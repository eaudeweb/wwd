<?php

namespace Drupal\wwd_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'EventsMapBlock' Block.
 *
 * @Block(
 *   id = "events_map_block",
 *   admin_label = @Translation("Events Map"),
 *   category = @Translation("Wwd Map"),
 * )
 */
class EventsMapBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The Events UN manager.
   *
   * @var \Drupal\wwd_core\Services\EventsManager
   */
  protected $eventsManager;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->moduleExtensionList = $container->get('extension.list.module');
    $instance->eventsManager = $container->get('wwd_core.events_manager');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get default vars.
    $viewBuilder = $this->eventsManager->getEntityTypeManager()
      ->getViewBuilder('node');
    $markers = [];
    // Get list of available countries.
    $countryIsoCodes = $this->eventsManager->getCountryIsoCodes();
    // Get api access token and map style.
    $config = $this->eventsManager->getSettings();
    // Retrieve event data.
    $events = $this->eventsManager->getUpcomingEvents(TRUE);
    if (!empty($events)) {
      foreach ($events as $node) {
        // Render the node in teaser view for popup.
        $entity = $this->eventsManager->getEntityRepository()
          ->getTranslationFromContext($node);
        $preRender = $viewBuilder->view($entity, 'teaser_xs');
        $markers[] = [
          'popup' => $this->renderer->render($preRender),
          'coords' => $entity->get('field_geolocation')->getValue()[0],
        ];
      }
    }
    $build = [
      '#theme' => 'events_map_block',
      '#attached' => [
        'library' => [
          'wwd_core/map',
        ],
        'drupalSettings' => [
          'wwd_map' => [
            'countries' => $countryIsoCodes,
            'markers' => $markers,
            'module_path' => $this->moduleExtensionList->getPath('wwd_core'),
            'access_token' => $config->get('mapbox_token') ?? NULL,
            'map_style' => $config->get('mapbox_style') ?? NULL,
          ],
        ],
      ],
    ];

    return $build;
  }

}
