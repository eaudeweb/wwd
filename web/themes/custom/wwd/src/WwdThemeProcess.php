<?php

namespace Drupal\wwd;

use Drupal\views\Views;
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
   * Constructs a new EntityOperations object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager')
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
    // Parse multimedia block.
    if ($element['#derivative_plugin_id'] == 'multimedia' ||
    (!empty($element['content']['#block_content']->type->target_id) && $element['content']['#block_content']->type->target_id == 'multimedia')) {
      // Get content.
      $content = $element['content']['#block_content'];
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
  }

}
