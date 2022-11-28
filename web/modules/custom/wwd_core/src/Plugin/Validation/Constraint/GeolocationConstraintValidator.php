<?php

namespace Drupal\wwd_core\Plugin\Validation\Constraint;

use Drupal\geolocation\GeocoderManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Validates the GeolocationConstraint constraint.
 */
class GeolocationConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The geocoder manager service.
   *
   * @var \Drupal\geolocation\GeocoderManager
   */
  protected $geocoderManager;

  /**
   * Constructs a new GeolocationConstraintValidator object.
   *
   * @param \Drupal\geolocation\GeocoderManager $geocoder
   *   The geocoder manager.
   */
  public function __construct(GeocoderManager $geocoder) {
    $this->geocoderManager = $geocoder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.geolocation.geocoder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    // Limit our validation for events content type currently.
    if ($entity->bundle() !== 'events') {
      return;
    }
    // Wait for country validation first.
    if (empty($entity->get('field_country')->entity)) {
      return;
    }
    // Grab all address fields and validate the location.
    $query['street'] = $entity->get('field_street_address')->getString();
    $query['country'] = $entity->get('field_country')->entity->getName();
    $query['city'] = $entity->get('field_city')->getString();
    // Call geocoder provider.
    $plugin_manager = $this->geocoderManager;
    $nominatimPlugin = $plugin_manager->getGeocoder('wwd_nominatim');
    // Check if location exists.
    $result = $nominatimPlugin->geocode($query);
    if (empty($result)) {
      $this->context->addViolation($constraint->message);
    }
  }

}
