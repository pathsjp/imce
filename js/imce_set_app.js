// $Id$
//When imce url contains &app=appName|fileProperty1@correspondingFieldId1|fileProperty2@correspondingFieldId2|...
//the specified fields are filled with the specified properties of the selected file.

var appFields = {}, appWindow = (top.appiFrm||window).opener;

//execute when imce loads.
imce.hooks.load.push(function(win) {
  var data = decodeURIComponent(location.href.substr(location.href.lastIndexOf('app=')+4)).split('|');
  var appName = data.shift();
  //run custom onload function if avaliable.
  if (data[0].indexOf('@') < 0 && $.isFunction(appWindow[data[0]])) {
    return appWindow[data[0]](win);
  }
  //set send to
  imce.setSendTo(Drupal.t('Send to @app', {'@app': appName}), appFinish);
  //extract fields
  for (var i in data) {
    var arr = data[i].split('@');
    appFields[arr[0]] = arr[1];
  }
  //highlight file
  if (appFields['url']) {
    if (appFields['url'].indexOf(',') > -1) {
      var arr = appFields['url'].split(',');
      for (var i in arr) {
        if ($('#'+ arr[i], appWindow.document).size()) {
          appFields['url'] = arr[i];
          break;
        }
      }
    }
    var filename = $('#'+ appFields['url'], appWindow.document).val();
    imce.highlight(filename.substr(filename.lastIndexOf('/')+1));
  }
});

//sendTo function
var appFinish = function(file, win) {
  var doc = $(appWindow.document);
  for (var i in appFields) {
    doc.find('#'+ appFields[i]).val(file[i]);
  }
  if (appFields['url']) {
    try{doc.find('#'+ appFields['url']).blur().change().focus()}catch(e){};
    try{doc.find('#'+ appFields['url']).trigger('onblur').trigger('onchange').trigger('onfocus')}catch(e){};//inline events
  }
  appWindow.focus();
  win.close();
};
