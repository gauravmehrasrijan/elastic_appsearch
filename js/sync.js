/**
* @file
*/

(function ($, Drupal) {
Drupal.AjaxCommands.prototype.sync = function (ajax, response, status) {
  console.log(response.message);
}

})(jQuery, Drupal);
