<?php
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\elastic_appsearch\Entity\Server;
use Drupal\Core\Render\Element;
use Drupal\Core\Url ;
use Drupal\elastic_appsearch\Utility\Common;
/**
 * Prepares variables for elastic_appsearch_admin_fields_table form templates.
 *
 * Default template: elastic-appsearch-admin-fields-table.html.twig.
 *
 * @param array &$variables
 *   Associative array of template variables, with the following structure:
 *   - element: Associative array with the following keys:
 *     - form: A render element representing the form.
 *     - note: The table note.
 */
function template_preprocess_elastic_appsearch_admin_fields_table(array &$variables) {
  $form = $variables['element'];
  $rows = [];
  if (!empty($form['fields'])) {
    foreach (Element::children($form['fields']) as $name) {
      $row = [];
      foreach (Element::children($form['fields'][$name]) as $field) {
        if ($cell = render($form['fields'][$name][$field])) {
          $row[] = $cell;
        }
      }
      $row = [
        'data' => $row,
        'data-field-row-id' => $name,
      ];
      if (!empty($form['fields'][$name]['description']['#value'])) {
        $row['title'] = strip_tags($form['fields'][$name]['description']['#value']);
      }
      $rows[] = $row;
    }
  }

  $variables['note'] = $form['note'] ?? '';
  unset($form['note'], $form['submit']);

  $variables['table'] = [
    '#theme' => 'table',
    '#header' => $form['#header'],
    '#rows' => $rows,
    '#empty' => t('No fields have been added for this datasource.'),
  ];
}

/**
 * Prepares variables for search_api_index templates.
 *
 * Default template: search-api-index.html.twig.
 *
 * @param array &$variables
 *   An associative array containing:
 *   - index: The search index to display.
 */
function template_preprocess_engine(array &$variables) {
  // kint($variables); exit;
  $engine = $variables['engine'];
  $tracker = $engine->getTrackerInstance();
  $indexed_count = $tracker->getIndexedItemsCount();
  $total_count = $tracker->getTotalItemsCount();
  // Initialize the $rows variable which will hold the different parts of server
  // information.
  $rows = [];
  // Create a row template with references so we don't have to deal with the
  // complicated structure for each individual row.
  $row = [
    'data' => [
      ['header' => TRUE],
      '',
    ],
    'class' => [],
  ];
  // Get the individual parts of the row by reference.
  $label = &$row['data'][0]['data'];
  $info = &$row['data'][1];
  $classes = &$row['class'];

  $label = "Status";
  $info = $engine->status() ? "Enabled" : "Disabled";
  $rows[] = Common::deepCopy($row);

  $label = "Total Documents";
  $info = $total_count;
  $rows[] = Common::deepCopy($row);

  $label = "Indexed Documents";
  $info = $indexed_count;
  $rows[] = Common::deepCopy($row);
  
  $label = t('Server');
  $info = $engine->getServer();
  $rows[] = Common::deepCopy($row);

  // Add the indexing progr ess bar.
  $variables['index_progress'] = [
    '#theme' => 'progress_bar',
    '#percent' => $total_count ? (int) (100 * $indexed_count / $total_count) : 100,
    '#message' => t('@indexed/@total indexed', ['@indexed' => $indexed_count, '@total' => $total_count]),
  ];

  // Append the index info table to the output.
  $variables['table'] = [
    '#theme' => 'table',
    '#rows' => $rows,
    '#attributes' => [
      'class' => [
        'search-api-index-summary',
      ],
    ],
  ];

}