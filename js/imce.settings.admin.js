(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.myBehavior = {
    attach: function (context, settings) {
      $('#quality_percent_gd').text($(':input[name="quality_gd"]', context).val())
      $(':input[name="quality_gd"]', context).change(function () {
        $('#quality_percent_gd').text($(this).val())
      });

      $('#quality_percent_imagick').text($(':input[name="quality_imagick"]', context).val())
      $(':input[name="quality_imagick"]', context).change(function () {
        $('#quality_percent_imagick').text($(this).val())
      });
    }
  };

})(jQuery, Drupal);
