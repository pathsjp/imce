(function ($) {
  // Global container.
  var imce = {
    tree: {},
    findex: [],
    fids: {},
    selected: {},
    selcount: 0,
    ops: {},
    cache: {},
    urlId: {},
    msgQueue: [],
    vars: { previewImages: 1, cache: 1 },
    hooks: { load: [], list: [], navigate: [], cache: [] },
  };
  window.imce = imce;

  // Initiate imce.
  imce.initiate = function () {
    imce.conf = Drupal.settings.imce || {};
    if (imce.conf.error != false) {
      return;
    }
    imce.ie = Number((navigator.userAgent.match(/msie (\d+)/i) || ['', 0])[1]);
    imce.FLW = imce.el('file-list-wrapper');
    imce.SBW = imce.el('sub-browse-wrapper');
    imce.NW = imce.el('navigation-wrapper');
    imce.BW = imce.el('browse-wrapper');
    imce.PW = imce.el('preview-wrapper');
    imce.FW = imce.el('forms-wrapper');
    imce.updateUI();
    // Process initial status messages.
    imce.prepareMsgs();
    // Build directory tree.
    imce.initiateTree();
    // Set the default list-hook.
    imce.hooks.list.unshift(imce.processRow);
    // Process file list.
    imce.initiateList();
    // Prepare operation tabs.
    imce.initiateOps();
    imce.refreshOps();
    // Bind global error handler.
    $(document).ajaxError(imce.ajaxError);
    // Run functions set by external applications.
    imce.invoke('load', window);
  };

  // Process navigation tree.
  imce.initiateTree = function () {
    $('#navigation-tree li').each(function (i) {
      var a = this.firstChild,
        txt = a.firstChild;
      txt && (txt.data = imce.decode(txt.data));
      var branch = (imce.tree[a.title] = {
        a: a,
        li: this,
        ul: this.lastChild.tagName == 'UL' ? this.lastChild : null,
      });
      if (a.href) {
        imce.dirClickable(branch);
      }
      imce.dirCollapsible(branch);
    });
  };

  // Add a dir to the tree under parent.
  imce.dirAdd = function (dir, parent, clickable) {
    if (imce.tree[dir]) {
      return clickable ? imce.dirClickable(imce.tree[dir]) : imce.tree[dir];
    }
    var parent = parent || imce.tree['.'];
    parent.ul = parent.ul ? parent.ul : parent.li.appendChild(imce.newEl('ul'));
    var branch = imce.dirCreate(
      dir,
      imce.decode(dir.substr(dir.lastIndexOf('/') + 1)),
      clickable
    );
    parent.ul.appendChild(branch.li);
    return branch;
  };

  // Create list item for navigation tree.
  imce.dirCreate = function (dir, text, clickable) {
    if (imce.tree[dir]) {
      return imce.tree[dir];
    }
    var branch = (imce.tree[dir] = {
      li: imce.newEl('li'),
      a: imce.newEl('a'),
    });
    $(branch.a)
      .addClass('folder')
      .text(text)
      .attr('title', dir)
      .appendTo(branch.li);
    imce.dirCollapsible(branch);
    return clickable ? imce.dirClickable(branch) : branch;
  };

  // Change currently active directory.
  imce.dirActivate = function (dir) {
    if (dir != imce.conf.dir) {
      if (imce.tree[imce.conf.dir]) {
        $(imce.tree[imce.conf.dir].a).removeClass('active');
      }
      $(imce.tree[dir].a).addClass('active');
      imce.conf.dir = dir;
    }
    return imce.tree[imce.conf.dir];
  };

  // Make a dir accessible.
  imce.dirClickable = function (branch) {
    if (branch.clkbl) {
      return branch;
    }
    $(branch.a)
      .attr('href', '#')
      .removeClass('disabled')
      .click(function () {
        imce.navigate(this.title);
        return false;
      });
    branch.clkbl = true;
    return branch;
  };

  // Sub-directories expand-collapse ability.
  imce.dirCollapsible = function (branch) {
    if (branch.clpsbl) {
      return branch;
    }
    $(imce.newEl('span'))
      .addClass('expander')
      .html('&nbsp;')
      .click(function () {
        if (branch.ul) {
          $(branch.ul).toggle();
          $(branch.li).toggleClass('expanded');
          imce.ie && $('#navigation-header').css('top', imce.NW.scrollTop);
        }
        else if (branch.clkbl) {
          $(branch.a).click();
        }
      })
      .prependTo(branch.li);
    branch.clpsbl = true;
    return branch;
  };

  // Update navigation tree after getting subdirectories.
  imce.dirSubdirs = function (dir, subdirs) {
    var branch = imce.tree[dir];
    if (subdirs && subdirs.length) {
      var prefix = dir == '.' ? '' : dir + '/';
      for (var i = 0; i < subdirs.length; i++) {
        // Add subdirectories.
        imce.dirAdd(prefix + subdirs[i], branch, true);
      }
      $(branch.li).removeClass('leaf').addClass('expanded');
      $(branch.ul).show();
    }
    else if (!branch.ul) {
      // No subdirs->leaf.
      $(branch.li).removeClass('expanded').addClass('leaf');
    }
  };

  // Process file list.
  imce.initiateList = function (cached) {
    var L = imce.hooks.list,
      dir = imce.conf.dir,
      token = {
        '%dir': dir == '.' ? $(imce.tree['.'].a).text() : imce.decode(dir),
      };
    imce.findex = [];
    imce.fids = {};
    imce.selected = {};
    imce.selcount = 0;
    imce.vars.lastfid = null;
    imce.tbody = imce.el('file-list').tBodies[0];
    if (imce.tbody.rows.length) {
      for (var row, i = 0; (row = imce.tbody.rows[i]); i++) {
        var fid = row.id;
        imce.findex[i] = imce.fids[fid] = row;
        if (cached) {
          if (imce.hasC(row, 'selected')) {
            imce.selected[(imce.vars.lastfid = fid)] = row;
            imce.selcount++;
          }
        }
        else {
          for (var func, j = 0; (func = L[j]); j++) {
            func(row);
          }
        }
      }
    }
    if (!imce.conf.perm.browse) {
      imce.setMessage(
        Drupal.t('File browsing is disabled in directory %dir.', token),
        'error'
      );
    }
  };

  // Add a file to the list. (having properties name,size,formatted size,width,height,date,formatted date).
  imce.fileAdd = function (file) {
    var row,
      fid = file.name,
      i = imce.findex.length,
      attr = ['name', 'size', 'width', 'height', 'date'];
    if (!(row = imce.fids[fid])) {
      row = imce.findex[i] = imce.fids[fid] = imce.tbody.insertRow(i);
      for (var j in attr) {
        row.insertCell(j).className = attr[j];
      }
    }
    row.cells[0].innerHTML = row.id = fid;
    row.cells[1].innerHTML = file.fsize;
    row.cells[1].id = file.size;
    row.cells[2].innerHTML = file.width;
    row.cells[3].innerHTML = file.height;
    row.cells[4].innerHTML = file.fdate;
    row.cells[4].id = file.date;
    imce.invoke('list', row);
    if (imce.vars.prvfid == fid) {
      imce.setPreview(fid);
    }
    if (file.id) {
      imce.urlId[imce.getURL(fid)] = file.id;
    }
  };

  // Remove a file from the list.
  imce.fileRemove = function (fid) {
    var row = imce.fids[fid];
    if (!row) {
      return;
    }
    imce.fileDeSelect(fid);
    imce.findex.splice(row.rowIndex, 1);
    $(row).remove();
    delete imce.fids[fid];
    if (imce.vars.prvfid == fid) {
      imce.setPreview();
    }
  };

  // Return a file object containing all properties.
  imce.fileGet = function (fid) {
    var file = imce.fileProps(fid);
    if (file) {
      file.name = imce.decode(fid);
      file.url = imce.getURL(fid);
      file.relpath = imce.getRelpath(fid);
      // File id for newly uploaded files.
      file.id = imce.urlId[file.url] || 0;
    }
    return file;
  };

  // Return file properties embedded in html.
  imce.fileProps = function (fid) {
    var row = imce.fids[fid];
    return row
      ? {
          size: row.cells[1].innerHTML,
          bytes: row.cells[1].id * 1,
          width: row.cells[2].innerHTML * 1,
          height: row.cells[3].innerHTML * 1,
          date: row.cells[4].innerHTML,
          time: row.cells[4].id * 1,
        }
      : null;
  };

  // Simulate row click. selection-highlighting.
  imce.fileClick = function (row, ctrl, shft, focus) {
    if (!row) {
      return;
    }
    var fid = typeof row == 'string' ? row : row.id;
    if (ctrl || fid == imce.vars.prvfid) {
      imce.fileToggleSelect(fid, focus);
    }
    else if (shft) {
      var last = imce.lastFid();
      var start = last ? imce.fids[last].rowIndex : -1;
      var end = imce.fids[fid].rowIndex;
      var step = start > end ? -1 : 1;
      while (start != end) {
        start += step;
        imce.fileSelect(imce.findex[start].id, focus);
      }
    }
    else {
      for (var fname in imce.selected) {
        imce.fileDeSelect(fname);
      }
      imce.fileSelect(fid, focus);
    }
    // Set preview.
    imce.setPreview(imce.selcount == 1 ? imce.lastFid() : null);
  };

  // File select/deselect functions.
  imce.fileSelect = function (fid, focus) {
    if (imce.selected[fid] || !imce.fids[fid]) {
      return;
    }
    imce.selected[fid] = imce.fids[(imce.vars.lastfid = fid)];
    var $row = $(imce.selected[fid]).addClass('selected');
    if (focus) {
      $row.focus();
    }
    imce.selcount++;
  };
  imce.fileDeSelect = function (fid, focus) {
    if (!imce.selected[fid] || !imce.fids[fid]) {
      return;
    }
    if (imce.vars.lastfid == fid) {
      imce.vars.lastfid = null;
    }
    var $row = $(imce.selected[fid]).removeClass('selected');
    if (focus) {
      $row.focus();
    }
    delete imce.selected[fid];
    imce.selcount--;
  };
  imce.fileToggleSelect = function (fid, focus) {
    imce['file' + (imce.selected[fid] ? 'De' : '') + 'Select'](fid, focus);
  };

  // Process file operation form and create operation tabs.
  imce.initiateOps = function () {
    imce.setHtmlOps();
    imce.setUploadOp();
    // Thumb, delete, resize.
    imce.setFileOps();
  };

  // Process existing html ops.
  imce.setHtmlOps = function () {
    $(imce.el('ops-list'))
      .children('li')
      .each(function () {
        if (!this.firstChild) {
          return $(this).remove();
        }
        var name = this.id.substr(8);
        /** @type any */
        var Op = (imce.ops[name] = {
          div: imce.el('op-content-' + name),
          li: imce.el('op-item-' + name),
        });
        Op.a = Op.li.firstChild;
        Op.title = Op.a.innerHTML;
        $(Op.a).click(function () {
          imce.opClick(name);
          return false;
        });
      });
  };

  // Convert upload form to an op.
  imce.setUploadOp = function () {
    var el,
      form = imce.el('imce-upload-form');
    if (!form) {
      return;
    }
    $(form)
      .ajaxForm(imce.uploadSettings())
      .find('fieldset')
      .each(function () {
        // Clean up fieldsets.
        this.removeChild(this.firstChild);
        $(this).after(this.childNodes);
      })
      .remove();
    // Set html response flag.
    el = form.elements['files[imce]'];
    if (el && el.files && window.FormData) {
      if ((el = form.elements.html_response)) {
        el.value = 0;
      }
    }
    imce.opAdd({ name: 'upload', title: Drupal.t('Upload'), content: form });
  };

  // Convert fileop form submit buttons to ops.
  imce.setFileOps = function () {
    var form = imce.el('imce-fileop-form');
    if (!form) {
      return;
    }
    $(form.elements.filenames).parent().remove();
    $(form)
      .find('fieldset')
      .each(function () {
        // Remove fieldsets.
        var $sbmt = $('input:submit', this);
        if (!$sbmt.length) {
          return;
        }
        var Op = { name: $sbmt.attr('id').substr(5) };
        var func = function () {
          imce.fopSubmit(Op.name);
          return false;
        };
        $sbmt.click(func);
        Op.title = $(this).children('legend').remove().text() || $sbmt.val();
        Op.name == 'delete' ? (Op.func = func) : (Op.content = this.childNodes);
        imce.opAdd(Op);
      })
      .remove();
    imce.vars.opform = $(form).serialize();
  };

  // Refresh ops states. enable/disable.
  imce.refreshOps = function () {
    for (var p in imce.conf.perm) {
      if (imce.conf.perm[p]) {
        imce.opEnable(p);
      }
      else {
        imce.opDisable(p);
      }
    }
  };

  // Add a new file operation.
  imce.opAdd = function (op) {
    var oplist = imce.el('ops-list'),
      opcons = imce.el('op-contents');
    var name = op.name || 'op-' + $(oplist).children('li').length;
    var title = op.title || 'Untitled';
    /** @type any */
    var Op = (imce.ops[name] = { title: title });
    if (op.content) {
      Op.div = imce.newEl('div');
      $(Op.div)
        .attr({ id: 'op-content-' + name, class: 'op-content' })
        .appendTo(opcons)
        .append(op.content);
    }
    Op.a = imce.newEl('a');
    Op.li = imce.newEl('li');
    $(Op.a)
      .attr({ href: '#', name: name, title: title })
      .html('<span>' + title + '</span>')
      .click(imce.opClickEvent);
    $(Op.li)
      .attr('id', 'op-item-' + name)
      .append(Op.a)
      .appendTo(oplist);
    Op.func = op.func || imce.opVoid;
    return Op;
  };

  // Click event for file operations.
  imce.opClickEvent = function (e) {
    imce.opClick(this.name);
    return false;
  };

  // Void operation function.
  (imce.opVoid = function () {}),
    // Perform op click.
    (imce.opClick = function (name) {
      var Op = imce.ops[name],
        oldop = imce.vars.op;
      if (!Op || Op.disabled) {
        return imce.setMessage(
          Drupal.t('You can not perform this operation.'),
          'error'
        );
      }
      if (Op.div) {
        if (oldop) {
          var toggle = oldop == name;
          imce.opShrink(oldop, toggle ? 'fadeOut' : 'hide');
          if (toggle) {
            return false;
          }
        }
        var left = Op.li.offsetLeft;
        var $opcon = $('#op-contents').css({ left: 0 });
        $(Op.div).fadeIn('normal', function () {
          setTimeout(function () {
            if (imce.vars.op) {
              var $inputs = $('input', imce.ops[imce.vars.op].div);
              $inputs.eq(0).focus();
              // Form inputs become invisible in IE. Solution is as stupid as the behavior.
              $('html').hasClass('ie') &&
                $inputs.addClass('dummyie').removeClass('dummyie');
            }
          });
        });
        var diff = left + $opcon.width() - $('#imce-content').width();
        $opcon.css({ left: diff > 0 ? left - diff - 1 : left });
        $(Op.li).addClass('active');
        $(imce.opCloseLink).fadeIn(300);
        imce.vars.op = name;
      }
      Op.func(true);
      return true;
    });

  // Enable a file operation.
  imce.opEnable = function (name) {
    var Op = imce.ops[name];
    if (Op && Op.disabled) {
      Op.disabled = false;
      $(Op.li).show();
    }
  };

  // Disable a file operation.
  imce.opDisable = function (name) {
    var Op = imce.ops[name];
    if (Op && !Op.disabled) {
      Op.div && imce.opShrink(name);
      $(Op.li).hide();
      Op.disabled = true;
    }
  };

  // Hide contents of a file operation.
  imce.opShrink = function (name, effect) {
    if (imce.vars.op != name) {
      return;
    }
    var Op = imce.ops[name];
    $(Op.div).stop(true, true)[effect || 'hide']();
    $(Op.li).removeClass('active');
    $(imce.opCloseLink).hide();
    Op.func(false);
    imce.vars.op = null;
  };

  // Navigate to dir.
  imce.navigate = function (dir) {
    if (
      imce.vars.navbusy ||
      (dir == imce.conf.dir &&
        !confirm(Drupal.t('Do you want to refresh the current directory?')))
    ) {
      return;
    }
    var cache = imce.vars.cache && dir != imce.conf.dir;
    var set = imce.navSet(dir, cache);
    if (cache && imce.cache[dir]) {
      // Load from the cache.
      set.success({ data: imce.cache[dir] });
      set.complete();
    }
    else {
      $.ajax(set);
    }
  };
  // Ajax navigation settings.
  imce.navSet = function (dir, cache) {
    $(imce.tree[dir].li).addClass('loading');
    imce.vars.navbusy = dir;
    return {
      url: imce.ajaxURL('navigate', dir),
      type: 'GET',
      dataType: 'json',
      success: function (response) {
        if (response.data && !response.data.error) {
          if (cache) {
            imce.navCache(imce.conf.dir, dir);
          }
          imce.navUpdate(response.data, dir);
        }
        imce.processResponse(response);
      },
      complete: function () {
        $(imce.tree[dir].li).removeClass('loading');
        imce.vars.navbusy = null;
      },
    };
  };

  // Update directory using the given data.
  imce.navUpdate = function (data, dir) {
    var cached = data == imce.cache[dir],
      olddir = imce.conf.dir;
    if (cached) {
      data.files.id = 'file-list';
    }
    $(imce.FLW).html(data.files);
    imce.dirActivate(dir);
    imce.dirSubdirs(dir, data.subdirectories);
    $.extend(imce.conf.perm, data.perm);
    imce.refreshOps();
    imce.initiateList(cached);
    imce.setPreview(imce.selcount == 1 ? imce.lastFid() : null);
    imce.SBW.scrollTop = 0;
    imce.invoke('navigate', data, olddir, cached);
  };

  // Set cache.
  imce.navCache = function (dir, newdir) {
    var C = (imce.cache[dir] = {
      dir: dir,
      files: imce.el('file-list'),
      dirsize: imce.el('dir-size').innerHTML,
      perm: $.extend({}, imce.conf.perm),
    });
    C.files.id = 'cached-list-' + dir;
    imce.FW.appendChild(C.files);
    imce.invoke('cache', C, newdir);
  };

  // Validate upload form.
  imce.uploadValidate = function (data, form, options) {
    var path = $('#edit-imce').val();
    if (!path) {
      return false;
    }
    if (imce.conf.extensions != '*') {
      var ext = path.substr(path.lastIndexOf('.') + 1);
      if (
        (' ' + imce.conf.extensions + ' ').indexOf(
          ' ' + ext.toLowerCase() + ' '
        ) == -1
      ) {
        return imce.setMessage(
          Drupal.t(
            'Only files with the following extensions are allowed: %files-allowed.',
            { '%files-allowed': imce.conf.extensions }
          ),
          'error'
        );
      }
    }
    options.url = imce.ajaxURL('upload');
    imce.fopLoading('upload', true);
    return true;
  };

  // Settings for upload.
  imce.uploadSettings = function () {
    return {
      beforeSubmit: imce.uploadValidate,
      success: function (response) {
        try {
          imce.processResponse($.parseJSON(response));
        } catch (e) {}
      },
      complete: function () {
        imce.fopLoading('upload', false);
      },
      resetForm: true,
      dataType: 'text',
    };
  };

  // Validate default ops(delete, thumb, resize).
  imce.fopValidate = function (fop) {
    if (!imce.validateSelCount(1, imce.conf.filenum)) {
      return false;
    }
    switch (fop) {
      case 'delete':
        return confirm(Drupal.t('Delete selected files?'));
      case 'thumb':
        if (!$('input:checked', imce.ops['thumb'].div).length) {
          return imce.setMessage(
            Drupal.t('Please select a thumbnail.'),
            'error'
          );
        }
        return imce.validateImage();
      case 'resize':
        var w = imce.el('edit-width').value,
          h = imce.el('edit-height').value;
        var maxDim = imce.conf.dimensions.split('x');
        var maxW = maxDim[0] * 1,
          maxH = maxW ? maxDim[1] * 1 : 0;
        if (
          !/^[1-9][0-9]*$/.test(w) ||
          !/^[1-9][0-9]*$/.test(h) ||
          (maxW && (maxW < w * 1 || maxH < h * 1))
        ) {
          return imce.setMessage(
            Drupal.t(
              'Please specify dimensions within the allowed range that is from 1x1 to @dimensions.',
              {
                '@dimensions': maxW
                  ? imce.conf.dimensions
                  : Drupal.t('unlimited'),
              }
            ),
            'error'
          );
        }
        return imce.validateImage();
    }

    var func = fop + 'OpValidate';
    if (imce[func]) {
      return imce[func](fop);
    }
    return true;
  };

  // Submit wrapper for default ops.
  imce.fopSubmit = function (fop) {
    switch (fop) {
      case 'thumb':
      case 'delete':
      case 'resize':
        return imce.commonSubmit(fop);
    }
    var func = fop + 'OpSubmit';
    if (imce[func]) {
      return imce[func](fop);
    }
  };

  // Common submit function shared by default ops.
  imce.commonSubmit = function (fop) {
    if (!imce.fopValidate(fop)) {
      return false;
    }
    imce.fopLoading(fop, true);
    $.ajax(imce.fopSettings(fop));
  };

  // Settings for default file operations.
  imce.fopSettings = function (fop) {
    return {
      url: imce.ajaxURL(fop),
      type: 'POST',
      dataType: 'json',
      success: imce.processResponse,
      complete: function (response) {
        imce.fopLoading(fop, false);
      },
      data:
        imce.vars.opform +
        '&filenames=' +
        encodeURIComponent(imce.serialNames()) +
        '&jsop=' +
        fop +
        (imce.ops[fop].div
          ? '&' + $('input, select, textarea', imce.ops[fop].div).serialize()
          : ''),
    };
  };

  // Toggle loading state.
  imce.fopLoading = function (fop, state) {
    var el = imce.el('edit-' + fop),
      func = state ? 'addClass' : 'removeClass';
    if (el) {
      $(el)[func]('loading');
      el.disabled = state;
    }
    else {
      $(imce.ops[fop].li)[func]('loading');
      imce.ops[fop].disabled = state;
    }
  };

  // Preview a file.
  imce.setPreview = function (fid) {
    var row,
      html = '';
    imce.vars.prvfid = fid;
    if (fid && (row = imce.fids[fid])) {
      var width = row.cells[2].innerHTML * 1;
      html =
        imce.vars.previewImages && width
          ? imce.imgHtml(fid, width, row.cells[3].innerHTML)
          : imce.decodePlain(fid);
      html =
        '<a href="#" onclick="imce.send(\'' +
        fid +
        '\'); return false;" title="' +
        (imce.vars.prvtitle || '') +
        '">' +
        html +
        '</a>';
    }
    imce.el('file-preview').innerHTML = html;
  };

  // Default file send function. sends the file to the new window.
  imce.send = function (fid) {
    fid && window.open(imce.getURL(fid));
  };

  // Add an operation for an external application to which the files are send.
  imce.setSendTo = function (title, func) {
    imce.send = function (fid) {
      fid && func(imce.fileGet(fid), window);
    };
    var opFunc = function () {
      if (imce.selcount < 1) {
        return imce.setMessage(Drupal.t('Please select a file.'), 'error');
      }
      imce.send(imce.lastFid());
    };
    imce.vars.prvtitle = title;
    return imce.opAdd({ name: 'sendto', title: title, func: opFunc });
  };

  // Move initial page messages into log.
  imce.prepareMsgs = function () {
    var msgs;
    if ((msgs = imce.el('imce-messages'))) {
      $('>div', msgs).each(function () {
        var type = this.className.split(' ')[1];
        var li = $('>ul li', this);
        if (li.length) {
          li.each(function () {
            imce.setMessage(this.innerHTML, type);
          });
        }
        else {
          imce.setMessage(this.innerHTML, type);
        }
      });
      $(msgs).remove();
    }
  };

  // Insert log message.
  imce.setMessage = function (msg, type) {
    var queue = imce.msgQueue;
    msg = '<div class="message ' + (type || 'status') + '">' + msg + '</div>';
    if (queue[queue.length - 1] === msg) {
      return;
    }
    queue.push(msg);
    var logs =
      imce.el('log-messages') ||
      $(imce.newEl('div'))
        .appendTo('#help-box-content')
        .before('<h4>' + Drupal.t('Log messages') + ':</h4>')
        .attr('id', 'log-messages')[0];
    $(logs).append(msg);
    // Already displaying the message box.
    if ($(imce.msgBox).queue().length) {
      return;
    }
    imce.startMsgBoxDisplay();
    return false;
  };

  imce.startMsgBoxDisplay = function () {
    var queue = imce.msgQueue;
    if (!queue.length) {
      return;
    }
    var $box = $(imce.msgBox);
    $box.css({ opacity: 0, display: 'block' }).html(queue.join('<br><br>'));
    queue.length = 0;
    $box.fadeTo(600, 1).fadeTo(imce.vars.msgT || 1000, 1);
    $box.queue(imce.finishMsgBoxDisplay);
  };

  imce.finishMsgBoxDisplay = function () {
    var $box = $(imce.msgBox);
    if ($box.hasClass('hover')) {
      setTimeout(imce.finishMsgBoxDisplay, 300);
    }
    else {
      $box.fadeOut(400, imce.startMsgBoxDisplay);
      $box.dequeue();
    }
  };

  // Invoke hooks.
  imce.invoke = function (hook) {
    var i, args, func, funcs;
    if ((funcs = imce.hooks[hook]) && funcs.length) {
      (args = $.makeArray(arguments)).shift();
      for (i = 0; (func = funcs[i]); i++) {
        func.apply(this, args);
      }
    }
  };

  // Process response.
  imce.processResponse = function (response) {
    if (response.data) {
      imce.resData(response.data);
    }
    if (response.messages) {
      imce.resMsgs(response.messages);
    }
  };

  // Process response data.
  imce.resData = function (data) {
    var i, added, removed;
    if ((added = data.added)) {
      var cnt = imce.findex.length;
      // Add new files or update existing.
      for (i in added) {
        imce.fileAdd(added[i]);
      }
      // Highlight single file.
      if (added.length == 1) {
        imce.highlight(added[0].name);
      }
      // New files added, scroll to bottom.
      if (imce.findex.length != cnt) {
        $(imce.SBW).animate({ scrollTop: imce.SBW.scrollHeight }).focus();
      }
    }
    if ((removed = data.removed)) {
      for (i in removed) {
        imce.fileRemove(removed[i]);
      }
    }
    imce.conf.dirsize = data.dirsize;
    imce.updateStat();
  };

  // Set response messages.
  imce.resMsgs = function (msgs) {
    for (var type in msgs) {
      for (var i in msgs[type]) {
        imce.setMessage(msgs[type][i], type);
      }
    }
  };

  // Return img markup.
  imce.imgHtml = function (fid, width, height) {
    return (
      '<img src="' +
      imce.getURL(fid, true) +
      '" width="' +
      width +
      '" height="' +
      height +
      '" alt="' +
      imce.decodePlain(fid) +
      '">'
    );
  };

  // Check if the file is an image.
  imce.isImage = function (fid) {
    return imce.fids[fid].cells[2].innerHTML * 1;
  };

  // Find the first non-image in the selection.
  imce.getNonImage = function (selected) {
    for (var fid in selected) {
      if (!imce.isImage(fid)) {
        return fid;
      }
    }
    return false;
  };

  // Validate current selection for images.
  imce.validateImage = function () {
    var nonImg = imce.getNonImage(imce.selected);
    return nonImg
      ? imce.setMessage(
          Drupal.t('%filename is not an image.', {
            '%filename': imce.decode(nonImg),
          }),
          'error'
        )
      : true;
  };

  // Validate number of selected files.
  imce.validateSelCount = function (Min, Max) {
    if (Min && imce.selcount < Min) {
      return imce.setMessage(
        Min == 1
          ? Drupal.t('Please select a file.')
          : Drupal.t('You must select at least %num files.', { '%num': Min }),
        'error'
      );
    }
    if (Max && Max < imce.selcount) {
      return imce.setMessage(
        Drupal.t('You are not allowed to operate on more than %num files.', {
          '%num': Max,
        }),
        'error'
      );
    }
    return true;
  };

  // Update file count and dir size.
  imce.updateStat = function () {
    imce.el('file-count').innerHTML = imce.findex.length;
    imce.el('dir-size').innerHTML = imce.conf.dirsize;
  };

  // Serialize selected files. return fids with a colon between them.
  imce.serialNames = function () {
    var str = '';
    for (var fid in imce.selected) {
      str += ':' + fid;
    }
    return str.substr(1);
  };

  // Get file url. re-encode & and # for mod rewrite.
  imce.getURL = function (fid, uncached) {
    var url = imce.getRelpath(fid);
    if (imce.conf.modfix) {
      url = url.replace(/%(23|26)/g, '%25$1');
    }
    url = imce.conf.furl + url;
    if (uncached) {
      var file = imce.fileProps(fid);
      url +=
        (url.indexOf('?') === -1 ? '?' : '&') +
        's' +
        file.bytes +
        'd' +
        file.time;
    }
    return url;
  };

  // Get encoded file path relative to root.
  imce.getRelpath = function (fid) {
    var dir = imce.conf.dir;
    return (dir === '.' ? '' : dir + '/') + fid;
  };

  // El. by id.
  imce.el = function (id) {
    return document.getElementById(id);
  };

  // Find the latest selected fid.
  imce.lastFid = function () {
    var fid = imce.vars.lastfid;
    if (!fid) {
      for (fid in imce.selected) {
      }
    }
    return fid;
  };

  // Create ajax url.
  imce.ajaxURL = function (op, dir) {
    return (
      imce.conf.url +
      (imce.conf.clean ? '?' : '&') +
      'jsop=' +
      op +
      '&dir=' +
      (dir || imce.conf.dir)
    );
  };

  // Fast class check.
  imce.hasC = function (el, name) {
    return (
      el.className && (' ' + el.className + ' ').indexOf(' ' + name + ' ') != -1
    );
  };

  // Highlight a single file.
  imce.highlight = function (fid) {
    if (imce.vars.prvfid) {
      imce.fileClick(imce.vars.prvfid);
    }
    imce.fileClick(fid, false, false, true);
  };

  // Process a row.
  imce.processRow = function (row) {
    var name = imce.decodePlain(row.id);
    row.cells[0].innerHTML = '<span>' + name + '</span>';
    row.onmousedown = function (e) {
      e = e || window.event;
      imce.fileClick(this, e.ctrlKey, e.shiftKey, true);
      return !(e.ctrlKey || e.shiftKey);
    };
    row.ondblclick = function (e) {
      imce.send(this.id);
      return false;
    };
    row.tabIndex = -1;
  };

  // Decode urls. uses unescape. can be overridden to use decodeURIComponent.
  imce.decode = function (str) {
    try {
      return decodeURIComponent(str);
    } catch (e) {}
    return str;
  };

  // Decode and convert to plain text.
  imce.decodePlain = function (str) {
    return Drupal.checkPlain(imce.decode(str));
  };

  // Global ajax error function.
  imce.ajaxError = function (e, response, settings, thrown) {
    imce.setMessage(
      Drupal.ajaxError(response, settings.url).replace(/\n/g, '<br />'),
      'error'
    );
  };

  // Convert button elements to standard input buttons.
  imce.convertButtons = function (form) {
    $('button:submit', form).each(function () {
      $(this).replaceWith(
        '<input type="submit" value="' +
          $(this).text() +
          '" name="' +
          this.name +
          '" class="form-submit" id="' +
          this.id +
          '" />'
      );
    });
  };

  // Create element.
  imce.newEl = function (name) {
    return document.createElement(name);
  };

  // Scroll syncronization for section headers.
  imce.syncScroll = function (scrlEl, fixEl, bottom) {
    var $fixEl = $(fixEl);
    var prop = bottom ? 'bottom' : 'top';
    var factor = bottom ? -1 : 1;
    var syncScrl = function (el) {
      $fixEl.css(prop, factor * el.scrollTop);
    };
    $(scrlEl).scroll(function () {
      var el = this;
      syncScrl(el);
      setTimeout(function () {
        syncScrl(el);
      });
    });
  };

  // Get UI ready. provide backward compatibility.
  imce.updateUI = function () {
    // File urls.
    var furl = imce.conf.furl,
      isabs = furl.indexOf('://') > -1;
    var absurls = (imce.conf.absurls = imce.vars.absurls || imce.conf.absurls);
    var host = location.host;
    var baseurl = location.protocol + '//' + host;
    if (furl.charAt(furl.length - 1) != '/') {
      furl = imce.conf.furl = furl + '/';
    }
    imce.conf.modfix = imce.conf.clean && furl.split('/')[3] === 'system';
    if (absurls && !isabs) {
      imce.conf.furl = baseurl + furl;
    }
    else if (!absurls && isabs && furl.indexOf(baseurl) == 0) {
      furl = furl.substr(baseurl.length);
      // Server base url is defined with a port which is missing in current page url.
      if (furl.charAt(0) === ':') {
        furl = furl.replace(/^:\d*/, '');
      }
      imce.conf.furl = furl;
    }
    // Convert button elements to input elements.
    imce.convertButtons(imce.FW);
    // Ops-list.
    $('#ops-list')
      .removeClass('tabs secondary')
      .addClass('clear-block clearfix');
    imce.opCloseLink = $(imce.newEl('a'))
      .attr({ id: 'op-close-link', href: '#', title: Drupal.t('Close') })
      .click(function () {
        imce.vars.op && imce.opClick(imce.vars.op);
        return false;
      })
      .appendTo('#op-contents')[0];
    // Navigation-header.
    if (!$('#navigation-header').length) {
      $(imce.NW)
        .children('.navigation-text')
        .attr('id', 'navigation-header')
        .wrapInner('<span></span>');
    }
    // Log.
    $('#log-prv-wrapper')
      .before($('#log-prv-wrapper > #preview-wrapper'))
      .remove();
    $('#log-clearer').remove();
    // Content resizer.
    $('#content-resizer').remove();
    // Message-box.
    imce.msgBox =
      imce.el('message-box') ||
      $(imce.newEl('div'))
        .attr('id', 'message-box')
        .prependTo('#imce-content')[0];
    $(imce.msgBox).hover(
      function () {
        $(this).addClass('hover');
      },
      function () {
        $(this).removeClass('hover');
      }
    );
    // Create help tab.
    var $hbox = $('#help-box');
    $hbox.is('a') &&
      $hbox.replaceWith(
        $(imce.newEl('div')).attr('id', 'help-box').append($hbox.children())
      );
    imce.hooks.load.push(function () {
      imce.opAdd({
        name: 'help',
        title: $('#help-box-title').remove().text(),
        content: $('#help-box').show(),
      });
    });
    // Add ie classes.
    imce.ie &&
      $('html').addClass('ie') &&
      imce.ie < 8 &&
      $('html').addClass('ie-7');
    // Enable box view for file list.
    imce.vars.boxW && imce.boxView();
    // Scrolling file list.
    imce.syncScroll(imce.SBW, '#file-header-wrapper');
    imce.syncScroll(imce.SBW, '#dir-stat', true);
    // Scrolling directory tree.
    imce.syncScroll(imce.NW, '#navigation-header');
  };
  // Initiate.
  $(document).ready(imce.initiate);
})(jQuery);
