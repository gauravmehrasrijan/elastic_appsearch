<?php

namespace Drupal\elastic_appsearch\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class SyncEngine.
 */
class SyncEngine implements CommandInterface {

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'sync',
      'message' => 'My Awesome Message',
    ];
  }

}
