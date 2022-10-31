<?php

namespace Drupal\wwd;

use Drupal\views\Views;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\wwd_core\Services\EventsManager;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Defines a class for reacting to entity/preprocess events.
 *
 * @internal
 */
class WwdThemeProcess implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Events manager.
   *
   * @var \Drupal\wwd_core\Services\EventsManager
   */
  protected $eventsManager;

  /**
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   Current route match.
   * @param \Drupal\Core\Sesssion\AccountProxyInterface $account
   *   Current user.
   * @param \Drupal\Core\Path\CurrentPathStack $path
   *   Current path.
   * @param \Drupal\wwd_core\Services\EventsManager $events_manager
   *   Events manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    CurrentRouteMatch $route_match,
    AccountProxyInterface $account,
    CurrentPathStack $path,
    EventsManager $events_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->routeMatch = $route_match;
    $this->currentUser = $account;
    $this->currentPath = $path;
    $this->eventsManager = $events_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('path.current'),
      $container->get('wwd_core.events_manager')
    );
  }

  /**
   * Preprocess blocks.
   *
   * @see hook_preprocess_block()
   */
  public function preprocessBlock(&$variables) {
    // Apply certain classes to blocks.
    if ($variables['elements']['#base_plugin_id'] == 'block_content') {
      $blockType = strtr($variables['content']['#block_content']->bundle(), '_', '-');
      $variables['attributes']['class'][] = 'block--' . $blockType;
    }
    // Get block element.
    $element =& $variables['elements'];
    $nodeManager = $this->entityTypeManager->getStorage('node');
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    // Get current langcode.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    // Get content.
    $content = $element['content']['#block_content'] ?? NULL;
    // Parse multimedia block.
    if ($element['#derivative_plugin_id'] == 'multimedia' ||
    (!empty($content->type->target_id) &&
    $content->type->target_id == 'multimedia')) {

      $variables['content']['referenced_nodes'] = [];
      $view_result = [];
      // Check what we want to show.
      $type = $content->get('field_multimedia_type')->getString();
      switch ($type) {
        case 'individual':
          if (!$content->get('field_poster')->isEmpty()) {
            $posterNodes = $content->get('field_poster')->referencedEntities();
            $referencedPosters = [];
            foreach ($posterNodes as $key => $node) {
              $referencedPosters[$key] = $view_builder
                ->view($node, 'poster_teaser', $langcode);
            }
            $variables['content']['referenced_nodes']['posters'] = $referencedPosters;
          }
          break;

        case 'posters':
          // Get view name.
          $viewName = 'multimedia';
          $displayId = 'posters_block';
          $view = Views::getView($viewName);
          // $view->setArguments($args);
          $view->setDisplay($displayId);
          $view->execute();
          // Get the results of the view.
          $view_result['posters'] = $view->result;
          break;

        case 'videos':
          // Get view name.
          $viewName = 'multimedia';
          $displayId = 'videos';
          $view = Views::getView($viewName);
          // $view->setArguments($args);
          $view->setDisplay($displayId);
          $view->execute();
          // Get the results of the view.
          $view_result['videos'] = $view->result;
          break;
      }
      // Check if the view is not empty and return results.
      if (!empty($view_result)) {
        // If the view returns results...
        foreach ($view->result as $type => $row) {
          $node = $row->_entity;
          // Check for translation.
          $variables['content']['referenced_nodes'][$type][] = $view_builder
            ->view($node, 'video_teaser', $langcode);
        }
      }
    }

    // Get all gradient/background options for background_gradient paragraph.
    if ($content instanceof BlockContent && $content->hasField('field_background') &&
      !$content->get('field_background')->isEmpty()) {
      $paragraph = $content->get('field_background')->entity;
      if ($paragraph instanceof Paragraph && $paragraph->bundle() == 'background_gradient') {
        $useGradient = $paragraph->get('field_use_gradient')->value ? $paragraph->get('field_use_gradient')->value : 0;
        $variables['background_color'] = $paragraph->get('field_background_color')->getString();
        $variables['gradient_type'] = $paragraph->get('field_gradient_type')->getString();
        if ($useGradient == 1) {
          $gradientOptions = '';
          $variables['use_gradient'] = TRUE;
          if ($variables['gradient_type'] !== 'radial-gradient') {
            $variables['direction'] = $paragraph->get('field_direction')->getString();
            if ($variables['direction'] !== 'default') {
              $gradientOptions .= $variables['direction'] . ', ';
            }
          }
          $variables['first_color'] = $paragraph->get('field_gradient_first_color')->getString();
          $gradientOptions .= $variables['first_color'] . ',';
          $variables['second_color'] = $paragraph->get('field_gradient_second_colour')->getString();
          $gradientOptions .= $variables['second_color'];

          $variables['gradient_options'] = $gradientOptions;
        }
      }
    }
    // Parse mapbox block.
    if ($element['#derivative_plugin_id'] == 'mapbox' ||
    (!empty($content->type->target_id) &&
    $content->type->target_id == 'mapbox')) {
      $theme = $content->get('field_theme')->value;
      $variables['theme'] = $theme;
      // For homepage theme add the latest upcoming event to the list.
      if ($theme == 'homepage') {
        $query = $nodeManager->getQuery()
          ->condition('type', 'events')
          ->condition('status', 1)
          ->range(0, 1)
          ->sort('field_event_date', 'DESC');
        $nodeId = $query->execute();
        if (!empty($nodeId)) {
          $nodeId = reset($nodeId);
          $event = $nodeManager->load($nodeId);
          // Pre-render event node.
          $variables['event'] = $view_builder
            ->view($event, 'teaser_xs', $langcode);
        }
      }
      else {
        // Grab the filters by country.
        $countries = $this->eventsManager->getCountryIsoCodes(TRUE);
        if (!empty($countries)) {
          $variables['filters'] = [
            '#type' => 'select',
            '#options' => ['_none' => 'Select Country'] + $countries,
            '#required' => FALSE,
            '#default_value' => '_none',
            '#empty_option' => $this->t('Select Country'),
            '#empty_value' => '_none',
            '#attributes' => [
              'class' => [
                'event-country-filter',
              ],
            ],
          ];
        }

      }
    }
  }

  /**
   * Preprocess html.
   *
   * @see hook_preprocess_html()
   */
  public function preprocessHtml(&$variables) {
    $node = $this->routeMatch->getParameter('node');
    // Add conditional body class if banner is added or not.
    if ($node instanceof Node) {
      if ($node->hasField('field_add_banner') &&
        $node->get('field_add_banner')->value == 1) {
        $variables['attributes']['class'][] = 'use-banner';
      }
    }
    $currentPath = $this->currentPath->getPath();
    // For registering new event page, change the default title.
    if (strpos($currentPath, '/node/add/events') !== FALSE) {
      if ($this->currentUser->isAnonymous()) {
        $variables['head_title']['title'] = $this->t('Register your event');
      }
    }
  }

  /**
   * Preprocess page title from branding block.
   *
   * @see hook_preprocess_page_title()
   */
  public function preprocessPageTitle(&$variables) {
    $currentPath = $this->currentPath->getPath();
    // For registering new event page, change the default title.
    if (strpos($currentPath, '/node/add/events') !== FALSE) {
      if ($this->currentUser->isAnonymous()) {
        $variables['title'] = $this->t('Register your event');
      }
    }
  }

  /**
   * Preprocess page.
   *
   * @see hook_preprocess_page()
   */
  public function preprocessPage(&$variables) {
    if (!empty($variables['node'])) {
      $node = $variables['node'];

      if ($node instanceof NodeInterface) {
        if ($node->getType() == 'page') {
          if ($node->get('field_add_banner')->value == 1) {
            $variables['use_banner'] = TRUE;
          }
          // Retrieve gradients if any.
          if ($node->get('field_use_gradient')->value == 1) {
            $variables['use_gradient'] = TRUE;
            if (!$node->get('field_background_gradient')->isEmpty()) {
              $paragraph = $node->get('field_background_gradient')->entity;
              if ($paragraph instanceof Paragraph && $paragraph->bundle() == 'background_gradient') {
                $variables['background_gradient'] = $this->entityTypeManager->getViewBuilder('paragraph')
                  ->view($paragraph);
              }
            }

          }
          // Retrieve banner title.
          $bannerTitle = $node->get('field_banner_title')->getString();
          $variables['banner_title'] = $bannerTitle !== '' ? $bannerTitle : $node->getTitle();
          // Set background.
          if (!$node->get('field_media_image')->isEmpty()) {
            $media = $node->get('field_media_image')->entity;
            $variables['media_url'] = $this->entityTypeManager->getViewBuilder('media')
              ->view($media, 'background');
          }
          if (!$node->get('field_video')->isEmpty()) {
            $media = $node->get('field_video')->entity;
            $variables['video_url'] = $this->entityTypeManager->getViewBuilder('media')
              ->view($media, 'background');
          }
        }
      }
    }
  }

}
