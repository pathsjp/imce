/**
 * @file
 * Drupal File plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {
  "use strict";

  CKEDITOR.plugins.add('imceModal', {
    init: function (editor) {
      editor.ui.addButton('imceModal', {
        label: 'IMCE Modal File Browser',
        command: 'imceModal',
        toolbar: 'insert',
        icon: this.path + 'plugins/ckeditor/icons/image-file-16.png'
      });
    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
