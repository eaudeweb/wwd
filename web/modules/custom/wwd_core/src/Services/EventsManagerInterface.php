<?php

namespace Drupal\wwd_core\Services;

/**
 * Provides an interface for Events manager service.
 */
interface EventsManagerInterface {

  /**
   * Map configuration machine name.
   */
  const WWD_SETTINGS = 'wwd_core.settings';

  /**
   * Retrieves country iso codes from country taxonomy vocabulary.
   *
   * @param bool $group_by_iso
   *   Groups elements by iso code as key and name as value.
   */
  public function getCountryIsoCodes($group_by_iso);

  /**
   * Retrieves upcoming events that are published and event date is later.
   *
   * @param bool $load
   *   Option to load entities or return just ids.
   */
  public function getUpcomingEvents($load);

}
