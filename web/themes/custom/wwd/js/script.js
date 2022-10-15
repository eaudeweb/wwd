/**
 * @file
 * Description.
*/

(function ($, Drupal, once) {
  Drupal.behaviors.general = {
    attach: function (context) {
    }
  };
})(jQuery, Drupal, once);

let scrollpos = window.scrollY
const header = document.getElementById("header")

window.addEventListener('scroll', function() {
  scrollpos = window.scrollY;

  if (scrollpos >= 50) {
    header.classList.remove("bg-tr")
    header.classList.add("h-sticky")
  } else {
    header.classList.add("bg-tr")
    header.classList.remove("h-sticky")
  }
})
