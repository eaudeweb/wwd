<?php

namespace Drupal\wwd_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines CountryCoordsImportForm class.
 */
class CountryCoordsImportForm extends FormBase {

  /**
   * Batch Builder.
   *
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Define CountryCoordsImportForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
    $this->batchBuilder = new BatchBuilder();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'country_coords_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#attributes' => [
        'class' => ['btn btn-primary'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get data from csv file and process it.
    $fileUrl = drupal_get_path('module', 'wwd_core') . '/assets/data/countries_codes_and_coordinates.csv';
    $data = $this->convertCsvtoArray($fileUrl);
    if (empty($data)) {
      return FALSE;
    }

    $this->batchBuilder
      ->setTitle($this->t('Processing'))
      ->setInitMessage($this->t('Initializing.'))
      ->setProgressMessage($this->t('Completed @current of @total.'))
      ->setErrorMessage($this->t('An error has occurred.'));
    $this->batchBuilder->setFile(drupal_get_path('module', 'wwd_core') . '/src/Form/CountryCoordsImportForm.php');
    $this->batchBuilder->addOperation([$this, 'processItems'], [
      $data,
    ]);
    $this->batchBuilder->setFinishCallback([$this, 'finished']);
    batch_set($this->batchBuilder->toArray());
  }

  /**
   * Processor for batch operations.
   */
  public function processItems($data, array &$context) {
    // Elements per operation.
    $limit = 10;

    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($data);
    }

    // Save items to array which will be changed during processing.
    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $data;
    }

    $counter = 0;
    if (!empty($context['sandbox']['items'])) {
      // Remove already processed items.
      if ($context['sandbox']['progress'] != 0) {
        array_splice($context['sandbox']['items'], 0, $limit);
      }

      foreach ($context['sandbox']['items'] as $item) {
        if ($counter != $limit) {
          $processedTerm = $this->processItem($item);
          if (!$processedTerm) {
            $context['results']['failed'][$item['iso3']] = $item['country'];
          }
          $counter++;
          $context['sandbox']['progress']++;

          $context['message'] = $this->t('Now processing item :progress of :count', [
            ':progress' => $context['sandbox']['progress'],
            ':count' => $context['sandbox']['max'],
          ]);

          // Increment total processed item values. Will be used in finished
          // callback.
          $context['results']['processed'] = $context['sandbox']['progress'];
        }
      }
    }

    // If not finished all tasks, we count percentage of process. 1 = 100%.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Processing batch item array.
   *
   * @param array $item
   *   Item array to process.
   */
  public function processItem(array $item): bool {
    // Define default vars.
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    // Retrieve item values to check.
    $iso3 = $item['iso3'];
    // Check if there is a term by Iso3 code first.
    $term = $termStorage->loadByProperties([
      'vid' => 'country',
      'field_iso3_code' => $iso3,
    ]);
    if (empty($term)) {
      return FALSE;
    }
    $term = reset($term);
    // Setup longitude and latitude.
    try {
      $term->set('field_latitude', $item['lat']);
      $term->set('field_longitude', $item['lng']);
      $term->save();
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Failed to save @country (@id) term.', [
        '@country' => $term->getName(),
        '@id' => $term->id(),
      ]));
    }
    return TRUE;
  }

  /**
   * Finished callback for batch.
   */
  public function finished($success, $results, $operations) {
    $this->messenger()->addMessage($this->t('Processed @count rows.', [
      '@count' => $results['processed'],
    ]));
    if (!empty($results['failed'])) {
      $this->messenger()->addWarning($this->t('Failed to add coords for @failures.', [
        '@failures' => implode(',', $results['failed']),
      ]));
    }
  }

  /**
   * Converts csv file to array.
   *
   * @param string $filename
   *   File url with name.
   * @param string $delimiter
   *   Csv delimiter.
   */
  public function convertCsvtoArray($filename = '', $delimiter = ','): array {
    if (!file_exists($filename) || !is_readable($filename)) {
      return FALSE;
    }

    $header = NULL;
    $data = [];

    if (($handle = fopen($filename, 'r')) !== FALSE) {
      // Loop through rows.
      while (($row = fgetcsv($handle, 5000, $delimiter)) !== FALSE) {
        if (!$header) {
          $header = $row;
        }
        else {
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }

    return $data;
  }

}
