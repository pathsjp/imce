/*global imce:true*/
(function ($, Drupal, imce) {
  'use strict';

  /**
   * @file
   * Defines Download plugin for Imce.
   */

  /**
   * Init handler for Download.
   */
  imce.bind('init', imce.downloadInit = function () {
    // Check if download permission exists.
    if (imce.hasPermission('browse_files')) {
      // Add download button
      imce.addTbb('download', {
        title: Drupal.t('Download'),
        permission: 'browse_files',
        handler: imce.downloadSelected,
        icon: 'download'
      });
    }
  });

  imce.downloadSelected = function () {
    var selectedItems = imce.getSelection();

    if (imce.validateDownload(selectedItems)) {

      // Create an invisible A element
      const a = document.createElement("a");
      a.style.display = "none";
      document.body.appendChild(a);

      // Set the HREF to a Blob representation of the data to be downloaded
      selectedItems.forEach(element => {
        if (element.type == 'file') {
          a.href = element.el.Item.getUrl(true, false)
          // Use download attribute to set set desired file name
          a.setAttribute("download", element.el.Item.name);
          // Trigger the download by simulating click
          a.click();
        } else
          imce.setMessage(Drupal.t('The selected folders can not be downloaded.'));
      });

      // Cleanup
      window.URL.revokeObjectURL(a.href);
      document.body.removeChild(a);
    }
  }

  /**
   * Validates item deletion.
   */
  imce.validateDownload = function (items) {
    return imce.activeFolder.isReady() && imce.validateCount(items) && imce.validatePermissions(items, 'browse_files') && imce.validatePredefinedPath(items);
  };

})(jQuery, Drupal, imce);
