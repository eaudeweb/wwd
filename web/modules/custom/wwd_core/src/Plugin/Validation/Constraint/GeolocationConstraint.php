<?php

namespace Drupal\wwd_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check if provided address is actually valid.
 *
 * @Constraint(
 *   id = "GeolocationConstraint",
 *   label = @Translation("Geolocation validation constraint", context = "Validation"),
 *   type = "string"
 * )
 */
class GeolocationConstraint extends Constraint {

  /**
   * Constraint message.
   *
   * @var string
   */
  public $message = 'Could not locate this address. Please provide a valid location.';

}
