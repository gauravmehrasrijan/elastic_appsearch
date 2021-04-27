<?php

/**
 * @file
 * Contains theme preprocessors.
 */

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\elastic_appsearch\Entity\Server;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\elastic_appsearch\Utility\Common;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;

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
  $engine = $variables['engine'];

  if (!$engine->getServerInstance()->isAvailable()) {
    return;
  }
  $engine_info = $engine->info();
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

  $label = t('Engine');
  $info = Link::fromTextAndUrl($engine->id(), $engine->toUrl('edit-form'));
  $rows[] = Common::deepCopy($row);

  $label = "Status";
  $info = $engine->status() ? "Enabled" : "Disabled";
  $rows[] = Common::deepCopy($row);

  $label = "Total Documents";
  $info = $total_count;
  $rows[] = Common::deepCopy($row);

  $label = "Documents Processed";
  $info = ($indexed_count) ? $indexed_count : $engine_info['document_count'];
  $rows[] = Common::deepCopy($row);

  $label = "Documents Index";
  $info = $engine_info['document_count'];
  $rows[] = Common::deepCopy($row);

  $failed = ($total_count - $engine_info['document_count']);
  if ($failed) {
    $label = "Failure Count";
    $info = $failed;
    $rows[] = Common::deepCopy($row);
  }

  $label = t('Server');
  $info = Link::fromTextAndUrl($engine->getServer(), $engine->getServerInstance()->toUrl('canonical'));
  $rows[] = Common::deepCopy($row);

  // Add the indexing progr ess bar.
  $variables['index_progress'] = [
    '#theme' => 'progress_bar',
    '#percent' => $total_count ? (int) (100 * $engine_info['document_count'] / $total_count) : 100,
    '#message' => t('@indexed/@total indexed', ['@indexed' => $engine_info['document_count'], '@total' => $total_count]),
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

/**
 * Prepares variables for search_api_index templates.
 *
 * Default template: search-api-index.html.twig.
 *
 * @param array &$variables
 *   An associative array containing:
 *   - index: The search index to display.
 */
function template_preprocess_server(array &$variables) {
  $server = $variables['server'];
  $engines_in_sync = $server->getEngines();
  $engines = $server->getClient()->listEngines();

  $headers = [
    'Engine ID',
    'Type',
    'Document Count',
    'Is Sync',
    'Datasources',
    'Operations'
  ];
  $rows = [];

  foreach ($engines['results'] as $engine) {
    $is_sync = isset($engines_in_sync[$engine['name']]);
    $rows[] = [
      'name' => ($is_sync) ?
      Link::fromTextAndUrl($engine['name'], $engines_in_sync[$engine['name']]->toUrl('canonical')) :
      $engine['name'],
      'type' => $engine['type'],
      'count' => $engine['document_count'],
      'is_sync' => ($is_sync) ? 'Yes' : 'No',
      'datasources' => ($is_sync) ? implode(', ', $engines_in_sync[$engine['name']]->datasources()) : '',
      'op' => (!$is_sync) ?
      Link::fromTextAndUrl('Sync Now',
        Url::fromRoute('entity.elastic_appsearch_engine.sync', ['elastic_appsearch_engine' => $engine['name']])
      ) :
      build_ajax_url($engine),
    ];
  }
  $variables['table'] = [
    '#theme' => 'table',
    '#header' => $headers,
    '#rows' => $rows,
    '#empty' => t('No Engine found on the server'),
    '#attached' => ['library' => ['core/drupal.dialog.ajax']]
  ];

  // Return theme('table', array('header' => $headers, 'rows' => $rows));.
}

/**
 * {@inheritdoc}
 */
function build_ajax_url($engine) {
  $link_options = [
    'attributes' => [
      'class' => [
        'use-ajax',
        'button',
        'button-action',
        'bg-green',
        'button--primary',
        'button--small',
      ],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode([
        'width' => 700,
      ])
    ]
  ];
  $url = Url::fromRoute(
    'elastic_appsearch.elastic_appsearch_server.engine_schema',
    ['elastic_appsearch_engine' => $engine['name']]
  );
  $url->setOptions($link_options);
  return Link::fromTextAndUrl('Click here', $url)->toString();
}
