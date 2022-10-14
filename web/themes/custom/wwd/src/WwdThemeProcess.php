<?php

namespace Drupal\wwd;

use Drupal\views\Views;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Defines a class for reacting to entity/preprocess events.
 *
 * @internal
 */
class WwdThemeProcess implements ContainerInjectionInterface {

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
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   Current route match.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    CurrentRouteMatch $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * Preprocess blocks.
   */
  public function preprocessBlock(&$variables) {
    // Apply certain classes to blocks.
    if ($variables['elements']['#base_plugin_id'] == 'block_content') {
      $blockType = strtr($variables['content']['#block_content']->bundle(), '_', '-');
      $variables['attributes']['class'][] = 'block--' . $blockType;
    }
    // Get block element.
    $element =& $variables['elements'];
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
              $translated = $node;
              if ($node->hasTranslation($langcode)) {
                $translated = $node->getTranslation($langcode);
              }
              $referencedPosters[$key] = $view_builder
                ->view($translated, 'poster_teaser');
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
      }
      if ($content->get('field_include_video_list')->value == 1) {
        // Get view name.
        $viewName = 'multimedia';
        $displayId = 'videos';
        $view = Views::getView($viewName);
        // $view->setArguments($args);
        $view->setDisplay($displayId);
        $view->execute();
        // Get the results of the view.
        $view_result['videos'] = $view->result;
      }
      // Check if the view is not empty and return results.
      if (!empty($view_result)) {
        // If the view returns results...
        foreach ($view->result as $type => $row) {
          $node = $row->_entity;
          if ($node->hasTranslation($langcode)) {
            $node = $node->getTranslation($langcode);
          }
          // Check for translation.
          $variables['content']['referenced_nodes'][$type] = $view_builder
            ->view($translated, 'video_teaser');
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
  }

  /**
   * Preprocess html.
   */
  public function preprocessHtml(&$variables) {
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof Node) {
      if ($node->hasField('field_add_banner') &&
        $node->get('field_add_banner')->value == 1) {
        $variables['attributes']['class'][] = 'use-banner';
      }
    }
  }

  /**
   * Preprocess page.
   */
  public function preprocessPage(&$variables) {
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
        $variables['has_video'] = FALSE;
        if (!$node->get('field_media_image')->isEmpty()) {
          $media = $node->get('field_media_image')->entity;
          if ($media->bundle() == 'video') {
            $variables['has_video'] = TRUE;
          }
          $variables['media_url'] = $this->entityTypeManager->getViewBuilder('media')
            ->view($media, 'background');
        }
      }
    }
  }

}
