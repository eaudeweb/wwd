<?php

namespace Drupal\wwd_core\Plugin\EntityReferenceSelection;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:upcoming_events",
 *   label = @Translation("Events selection"),
 *   entity_types = {"node"},
 *   group = "default",
 *   weight = 3
 * )
 */
class UpcomingEventsSelection extends NodeSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    if (empty($this->configuration['filter'])) {
      return $query;
    }
    $filter_settings = $this->configuration['filter'];
    foreach ($filter_settings as $field_name => $value) {
      if ($field_name == 'upcoming') {
        $now = new DrupalDateTime('now');
        $query->condition('field_event_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>=');
      }
      else {
        $query->condition($field_name, $value, '=');
      }

    }
    return $query;
  }

}
