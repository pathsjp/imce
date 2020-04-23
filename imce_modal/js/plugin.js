/**
 * @file
 * Drupal File plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {
  "use strict";

  CKEDITOR.plugins.add('imceModal', {
    init: function(editor) {
      editor.ui.addButton('imceModal', {
        label: 'IMCE Modal File Browser',
        command: 'imceModal',
        toolbar: 'insert',
        icon: this.path + 'plugins/ckeditor/icons/image-file-16.png'
      });

      editor.addCommand('imceModal',{
        exec: function (editor) {
          // Set existing values for future updates.
          let existingValues = {};

          // Prepare a save callback to be used upon saving the dialog.
          let saveCallback = function (returnValues) {
          };

          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          let dialogSettings = {
            title: editor.config.imceModalTitle,
            dialogClass: 'dialog-imce'
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url('imce_modal/form/imce_modal_dialog/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
        }
      });
    },
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
