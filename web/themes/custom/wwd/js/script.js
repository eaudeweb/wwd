/**
 * @file
 * Description.
*/

// (function ($, Drupal, once) {
//   Drupal.behaviors.general = {
//     attach: function (context) {
//     }
//   };
// })(jQuery, Drupal, once);

const navHeight = document.getElementById('header').offsetHeight;
document.documentElement.style.setProperty('--scroll-padding', navHeight + 'px');
