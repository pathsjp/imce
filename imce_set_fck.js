// $Id$
if (Drupal.jsEnabled) {
  $(window).load(imceInitiateFCK);
}
function imceInitiateFCK() {
  if ("undefined" != typeof(window.FCKeditorAPI)) {
    var width = 640;
    var height = 480;
    var types = ['Image', 'Link', 'Flash'];
    for (var i in FCKeditorAPI.__Instances) {
      var fck = FCKeditorAPI.__Instances[i];
      for (var t in types) {
        eval('fck.Config.'+types[t]+'Browser = true; fck.Config.'+types[t]+'BrowserURL = imceBrowserURL; fck.Config.'+types[t]+'BrowserWindowWidth = width; fck.Config.'+types[t]+'BrowserWindowHeight = height;');
      }
    }
  }
}
