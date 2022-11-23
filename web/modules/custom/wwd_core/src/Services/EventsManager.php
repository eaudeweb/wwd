<?php

namespace Drupal\wwd_core\Services;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Helper methods for Events management.
 */
class EventsManager implements EventsManagerInterface {

  use StringTranslationTrait, LoggerChannelTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  protected $configFactory;

  /**
   * The Current User.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Cache manager.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $staticCache;

  /**
   * Constructs a EventsManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config factory.
   * @param \Drupal\Core\Session\AccountProxy $account
   *   Current user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config,
    AccountProxy $account,
    EntityRepositoryInterface $entity_repository,
    CacheBackendInterface $cache
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config;
    $this->currentUser = $account;
    $this->entityRepository = $entity_repository;
    $this->staticCache = $cache;
  }

  /**
   * Retrieve config settings.
   */
  public function getSettings($config = NULL) {
    $settings = $config ?? static::WWD_SETTINGS;
    return $this->configFactory->getEditable($settings);
  }

  /**
   * Expose current user.
   */
  public function getCurrentUser() {
    return $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser->id());
  }

  /**
   * Expose entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Expose entity repository.
   */
  public function getEntityRepository() {
    return $this->entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryIsoCodes($group_by_iso = FALSE): array {
    $countries = [];
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $termStorage->loadTree('country', 0, NULL, TRUE);
    if (empty($terms)) {
      return $countries;
    }
    foreach ($terms as $id => $term) {
      $term = $this->entityRepository->getTranslationFromContext($term);
      if (!$term->get('field_iso3_code')->isEmpty()) {
        $countries[$id] = [
          'iso3' => $term->get('field_iso3_code')->getString(),
          'name' => $term->getName(),
          'lat' => $term->get('field_latitude')->getString(),
          'lng' => $term->get('field_longitude')->getString(),
        ];
      }
    }
    if ($group_by_iso && !empty($countries)) {
      $groupedCountries = [];
      array_walk($countries, function (&$v, $k) use (&$groupedCountries) {
        $iso = $v['iso3'];
        $groupedCountries[$iso] = $v['name'];
      });
      return $groupedCountries;
    }

    return $countries;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpcomingEvents($load = FALSE): array {
    $results = [];
    $now = new DrupalDateTime('now');
    $nodeManager = $this->entityTypeManager->getStorage('node');
    $query = $nodeManager->getQuery()
      ->condition('status', 1)
      ->condition('field_event_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>=');
    $results = $query->execute();
    if ($load) {
      $results = $nodeManager->loadMultiple($results);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventYearOptions() {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $options = &drupal_static(__FUNCTION__);
    if (is_null($options)) {
      $cid = 'wwd:message:year';
      $data = $this->staticCache->get($cid);
      if (!$data) {
        $options = [];
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
    return $options;
  }

}
