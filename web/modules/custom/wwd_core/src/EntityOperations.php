<?php

namespace Drupal\wwd_core;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\geolocation\GeocoderManager;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Defines a class for reacting to entity events.
 *
 * @internal
 */
class EntityOperations implements ContainerInjectionInterface {

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The geocoder manager service.
   *
   * @var \Drupal\geolocation\GeocoderManager
   */
  protected $geocoderManager;

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
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\geolocation\GeocoderManager $geocoder
   *   The geocoder manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    GeocoderManager $geocoder,
    AccountProxyInterface $account,
    CacheBackendInterface $cache
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->geocoderManager = $geocoder;
    $this->currentUser = $account;
    $this->staticCache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.geolocation.geocoder'),
      $container->get('current_user'),
      $container->get('cache.default')
    );
  }

  /**
   * Process node entity during delete/presave/update/insert events.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was just processed.
   * @param string $operation
   *   Operation made.
   *
   * @see hook_entity_HOOK()
   */
  public function processNode(EntityInterface $entity, $operation) {
    // Perform some entity modifications for events content type.
    if ($entity->bundle() == 'events' &&
    in_array($operation, ['presave'])) {
      // Grab all address fields and validate the location.
      $query['street'] = $entity->get('field_street_address')->getString();
      $query['country'] = $entity->get('field_country')->entity->getName();
      $query['city'] = $entity->get('field_city')->getString();
      // Call geocoder provider.
      $plugin_manager = $this->geocoderManager;
      $nominatimPlugin = $plugin_manager->getGeocoder('wwd_nominatim');
      // Check if location exists and save it for this node.
      $result = $nominatimPlugin->geocode($query);
      if (!empty($result)) {
        $entity->set('field_geolocation', [
          'lat' => $result['location']['lat'],
          'lng' => $result['location']['lng'],
        ]);
        if ($this->currentUser->isAnonymous()) {
          $entity->set('status', 0);
        }
      }

    }

    if ($entity->bundle() == 'message' &&
    in_array($operation, ['presave'])) {
      // Check if a message has a new year, and invalidate the
      // options cached used in the custom views filter for filtering by year.
      $cid = 'wwd:message:year';
      $data = $this->staticCache->get($cid);
      if ($data) {
        $options = $data->data;
        $date = $entity->field_message_date->value;
        if ($date) {
          $date = new DrupalDateTime($date, new \DateTimeZone('UTC'));
          $year = $date->format('Y');
          if (!isset($options[$year])) {
            Cache::invalidateTags(['wwd:message:year']);
          }
        }
      }

    }

  }

  /**
   * Adds custom constraint for entity of type node.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The master entity type list to alter.
   *
   * @see hook_entity_type_alter()
   */
  public function entityTypeAlter(array &$entity_types) {
    if (isset($entity_types['node'])) {
      $entity_types['node']->addConstraint('GeolocationConstraint');
    }
  }

}
