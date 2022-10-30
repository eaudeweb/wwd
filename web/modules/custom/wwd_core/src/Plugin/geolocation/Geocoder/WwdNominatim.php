<?php

namespace Drupal\wwd_core\Plugin\geolocation\Geocoder;

use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Drupal\geolocation\GeocoderBase;
use Drupal\Component\Serialization\Json;
use Drupal\geolocation\GeocoderInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\geolocation\GeocoderCountryFormattingManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Nominatim API.
 *
 * @Geocoder(
 *   id = "wwd_nominatim",
 *   name = @Translation("WWD Nominatim"),
 *   description = @Translation("See https://wiki.openstreetmap.org/wiki/Nominatim for details."),
 *   locationCapable = true,
 *   boundaryCapable = true,
 *   frontendCapable = false,
 *   reverseCapable = true,
 * )
 */
class WwdNominatim extends GeocoderBase implements GeocoderInterface {

  /**
   * Country formatter manager.
   *
   * @var \Drupal\geolocation\GeocoderCountryFormattingManager
   */
  protected $countryFormatterManager;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * WwdNominatim constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\geolocation\GeocoderCountryFormattingManager $geocoder_country_formatter_manager
   *   Country formatter manager.
   * @param \GuzzleHttp\ClientInterface $client
   *   Http client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config factory.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      GeocoderCountryFormattingManager $geocoder_country_formatter_manager,
      ClientInterface $client,
      ConfigFactoryInterface $config
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $geocoder_country_formatter_manager);

    $this->httpClient = $client;
    $this->configFactory = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.geocoder_country_formatting'),
      $container->get('http_client'),
      $container->get('config.factory')
    );
  }

  /**
   * Nominatim base URL.
   *
   * @var string
   */
  protected static $nominatimBaseUrl = 'https://nominatim.openstreetmap.org';

  /**
   * {@inheritdoc}
   */
  public function geocode($address) {
    if (empty($address)) {
      return FALSE;
    }

    $request_url_base = $this->getRequestUrlBase();
    $query = [
      'email' => $this->getRequestEmail(),
      'limit' => 1,
      'format' => 'json',
      'connect_timeout' => 5,
    ];
    if (is_array($address)) {
      foreach ($address as $key => $ad) {
        $query[$key] = $ad;
      }
      $url = Url::fromUri($request_url_base . '/search', ['query' => $query]);
    }
    else {
      $url = Url::fromUri($request_url_base . '/search/' . $address, ['query' => $query]);
    }

    try {
      $result = Json::decode($this->httpClient->get($url->toString())->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('geolocation', $e);
      return FALSE;
    }

    $location = [];

    if (empty($result[0])) {
      return FALSE;
    }
    else {
      $location['location'] = [
        'lat' => $result[0]['lat'],
        'lng' => $result[0]['lon'],
      ];
    }

    if (!empty($result[0]['boundingbox'])) {
      $location['boundary'] = [
        'lat_north_east' => $result[0]['boundingbox'][1],
        'lng_north_east' => $result[0]['boundingbox'][3],
        'lat_south_west' => $result[0]['boundingbox'][0],
        'lng_south_west' => $result[0]['boundingbox'][2],
      ];
    }

    if (!empty($result[0]['display_name'])) {
      $location['address'] = $result[0]['display_name'];
    }

    return $location;
  }

  /**
   * {@inheritdoc}
   */
  public function reverseGeocode($latitude, $longitude) {
    $request_url_base = $this->getRequestUrlBase();
    $url = Url::fromUri($request_url_base . '/reverse/', [
      'query' => [
        'lat' => $latitude,
        'lon' => $longitude,
        'email' => $this->getRequestEmail(),
        'limit' => 1,
        'format' => 'json',
        'connect_timeout' => 5,
        'addressdetails' => 1,
        'zoom' => 18,
      ],
    ]);

    try {
      $result = Json::decode($this->httpClient->get($url->toString())->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('geolocation', $e);
      return FALSE;
    }

    if (empty($result['address'])) {
      return FALSE;
    }

    $address_atomics = [];
    foreach ($result['address'] as $component => $value) {
      switch ($component) {
        case 'house_number':
          $address_atomics['houseNumber'] = $value;
          break;

        case 'road':
          $address_atomics['road'] = $value;
          break;

        case 'town':
          $address_atomics['village'] = $value;
          break;

        case 'city':
          $address_atomics['city'] = $value;
          break;

        case 'county':
          $address_atomics['county'] = $value;
          break;

        case 'postcode':
          $address_atomics['postcode'] = $value;
          break;

        case 'state':
          $address_atomics['state'] = $value;
          break;

        case 'country':
          $address_atomics['country'] = $value;
          break;

        case 'country_code':
          $address_atomics['countryCode'] = strtoupper($value);
          break;

        case 'suburb':
          $address_atomics['suburb'] = $value;
          break;
      }
    }

    return [
      'atomics' => $address_atomics,
      'elements' => $this->addressElements($address_atomics),
      'formatted_address' => empty($result['display_name']) ? '' : $result['display_name'],
    ];
  }

  /**
   * Retrieve base URL from setting or default.
   *
   * @return string
   *   Base URL.
   */
  protected function getRequestUrlBase() {
    $config = $this->configFactory->get('wwd_core.settings');

    if (!empty($config->get('nominatim_base_url'))) {
      $request_url = $config->get('nominatim_base_url');
    }
    else {
      $request_url = self::$nominatimBaseUrl;
    }
    return $request_url;
  }

  /**
   * Nominatim should be called with a request E-Mail.
   *
   * @return string
   *   Get Request Email.
   */
  protected function getRequestEmail() {
    $config = $this->configFactory->get('wwd_core.settings');

    if (!empty($config->get('nominatim_email'))) {
      $request_email = $config->get('nominatim_email');
    }
    else {
      $request_email = $this->configFactory->get('system.site')->get('mail');
    }
    return $request_email;
  }

}
