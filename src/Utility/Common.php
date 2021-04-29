<?php

namespace Drupal\elastic_appsearch\Utility;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\Markup;

define('DATASOURCE_ID_SEPARATOR', '/');

/**
 * Contains utility methods for the Search API.
 */
class Common {

  /**
   * Returns a deep copy of the input array.
   *
   * The behavior of PHP regarding arrays with references pointing to it is
   * rather weird. Therefore, this method should be used when making a copy of
   * such an array, or of an array containing references.
   *
   * This method will also omit empty array elements (that is, elements that
   * evaluate to FALSE according to PHP's native rules).
   *
   * @param array $array
   *   The array to copy.
   *
   * @return array
   *   A deep copy of the array.
   */
  public static function deepCopy(array $array) {
    $copy = [];
    foreach ($array as $k => $v) {
      if (is_array($v)) {
        if ($v = static::deepCopy($v)) {
          $copy[$k] = $v;
        }
      }
      elseif (is_object($v)) {
        $copy[$k] = clone $v;
      }
      elseif ($v) {
        $copy[$k] = $v;
      }
    }
    return $copy;
  }

  /**
   * {@inheritdoc}
   */
  public static function createCombinedId($node) {

    return $node->type . DATASOURCE_ID_SEPARATOR . $node->nid . ':' . $node->langcode;

  }

  /**
   * {@inheritdoc}
   */
  public static function splitCombinedId($combined_id) {
    if (strpos($combined_id, DATASOURCE_ID_SEPARATOR) !== FALSE) {
      return explode(DATASOURCE_ID_SEPARATOR, $combined_id, 2);
    }
    return [NULL, $combined_id];
  }

  /**
   * {@inheritdoc}
   */
  public static function isRunningInCli() {
    return php_sapi_name() === 'cli';
  }

  /**
   * {@inheritdoc}
   */
  public static function matches($value, array $settings) {
    $settings += [
      'default' => TRUE,
      'selected' => [],
    ];
    return in_array($value, $settings['selected']) != $settings['default'];
  }

  /**
   * Escapes HTML special characters in plain text, if necessary.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $text
   *   The text to escape.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   If a markup object was passed as $text, it is returned as-is. Otherwise,
   *   the text is escaped and returned
   */
  public static function escapeHtml($text) {
    if ($text instanceof MarkupInterface) {
      return $text;
    }

    return Markup::create(Html::escape((string) $text));
  }

  /**
  * excerpt first paragraph from html content
  * 
  **/
  public static function excerpt_paragraph($html, $max_char = 100, $trail='...' ){
    // temp var to capture the p tag(s)
    $matches= array();
    if ( preg_match( '/<p>[^>]+<\/p>/', $html, $matches) ){
        // found <p></p>
        $p = strip_tags($matches[0]);
    } else {
        $p = strip_tags($html);
    }
    //shorten without cutting words
    $p = self::short_str($p, $max_char );

    // remove trailing comma, full stop, colon, semicolon, 'a', 'A', space
    $p = rtrim($p, ',.;: aA' );

    // return nothing if just spaces or too short
    if (ctype_space($p) || $p=='' || strlen($p)<10) { return ''; }

    return '<p>'.$p.$trail.'</p>';
  }
  //

  /**
  * shorten string but not cut words
  * 
  **/
  public static function short_str( $str, $len, $cut = false ){
    
    if ( strlen( $str ) <= $len ) { 
      return $str; 
    }

    return ( $cut ? substr( $str, 0, $len ) : substr( $str, 0, strrpos( substr( $str, 0, $len ), ' ' ) ) );

  }

}
