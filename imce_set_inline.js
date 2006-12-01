// $Id$
if (Drupal.jsEnabled) {
  $(imceInitiateInline);
}

var imceActiveTextarea, imceActiveType;
function imceInitiateInline() {
  $('a.imce-insert-inline').each( function () {
    $(this.parentNode).css('display', 'block');
    $(this).click(function() {
      imceActiveTextarea = $('#'+this.name.split('|')[0]).get(0);
      imceActiveType = this.name.split('|')[1];
      window.open(this.href, '_imce_', 'width=640, height=480, resizable=1');
      return false;
    });
  });
}

//custom callback. hook:ImceFinish
function _imce_ImceFinish(path, w, h, imceWin) {
  var basename = path.substr(path.lastIndexOf('/')+1);
  imceActiveType = imceActiveType ? imceActiveType : (w&&h ? 'image' : 'link');
  var html = imceActiveType=='image' ? ('<img src="'+ path +'" width="'+ w +'" height="'+ h +'" alt="'+ basename +'" />') : ('<a href="'+ path +'">'+ basename +'</a>');
  imceInsertAtCursor(imceActiveTextarea, html);
  imceWin.close();
}

//insert html at cursor position
function imceInsertAtCursor(field, txt) {
  field.focus();
  if ('undefined' != typeof(field.selectionStart)) {
    field.value = field.value.substring(0, field.selectionStart) + txt + field.value.substring(field.selectionEnd, field.value.length);
  }
  else if (document.selection) {
    document.selection.createRange().text = txt;
  }
  else {
    field.value += txt;
  }
}
