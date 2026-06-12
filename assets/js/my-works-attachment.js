(function () {
  'use strict';

  function formatBytes(bytes) {
    if (!bytes || bytes < 1) {
      return '0 B';
    }
    var units = ['B', 'KB', 'MB', 'GB'];
    var i = 0;
    var n = bytes;
    while (n >= 1024 && i < units.length - 1) {
      n /= 1024;
      i += 1;
    }
    return (i === 0 ? n : n.toFixed(1)) + ' ' + units[i];
  }

  function fileIconClass(file) {
    if (!file || !file.name) {
      return 'bi-file-earmark';
    }
    var name = file.name.toLowerCase();
    var ext = name.indexOf('.') !== -1 ? name.split('.').pop() : '';
    if (/^(jpg|jpeg|png|gif|webp|bmp|svg)$/.test(ext)) {
      return 'bi-file-image';
    }
    if (/^(mp4|webm|mov|avi|mkv|m4v|wmv)$/.test(ext)) {
      return 'bi-file-play';
    }
    if (/^(mp3|wav|ogg|flac|aac|m4a)$/.test(ext)) {
      return 'bi-file-music';
    }
    if (/^(pdf)$/.test(ext)) {
      return 'bi-file-pdf';
    }
    if (/^(zip|rar|7z|tar|gz)$/.test(ext)) {
      return 'bi-file-zip';
    }
    if (/^(doc|docx)$/.test(ext)) {
      return 'bi-file-word';
    }
    if (/^(xls|xlsx|csv)$/.test(ext)) {
      return 'bi-file-excel';
    }
    if (/^(ppt|pptx)$/.test(ext)) {
      return 'bi-file-ppt';
    }
    return 'bi-file-earmark';
  }

  function initWidget(widget) {
    var input = widget.querySelector('.mw-attachment-input');
    var fileList = widget.querySelector('.mw-attachment-file-list');
    var progressWrap = widget.querySelector('.mw-attachment-progress');
    var progressBar = widget.querySelector('.mw-attachment-progress-bar .progress-bar');
    var progressPct = widget.querySelector('.mw-attachment-progress-pct');
    var progressLabel = widget.querySelector('.mw-attachment-progress-label');
    var errorEl = widget.querySelector('.mw-attachment-error');
    var maxBytes = parseInt(widget.getAttribute('data-max-bytes'), 10) || 0;
    var maxFiles = parseInt(widget.getAttribute('data-max-files'), 10) || 10;
    var selectedFiles = [];

    if (!input) {
      return;
    }

    function hideError() {
      if (errorEl) {
        errorEl.hidden = true;
        errorEl.textContent = '';
      }
    }

    function showError(msg) {
      if (errorEl) {
        errorEl.textContent = msg;
        errorEl.hidden = false;
      }
    }

    function setProgress(pct, label) {
      if (!progressWrap || !progressBar || !progressPct) {
        return;
      }
      progressWrap.hidden = false;
      if (typeof pct === 'number' && !isNaN(pct)) {
        var safe = Math.max(0, Math.min(100, Math.round(pct)));
        progressBar.style.width = safe + '%';
        progressBar.setAttribute('aria-valuenow', String(safe));
        progressPct.textContent = safe + '%';
        progressBar.classList.add('mw-attachment-progress-active');
      } else {
        progressPct.textContent = '…';
      }
      if (progressLabel && label) {
        progressLabel.textContent = label;
      }
    }

    function resetProgress() {
      if (!progressWrap || !progressBar || !progressPct) {
        return;
      }
      progressWrap.hidden = true;
      progressBar.style.width = '0%';
      progressBar.setAttribute('aria-valuenow', '0');
      progressBar.classList.remove('mw-attachment-progress-active');
      progressPct.textContent = '0%';
      if (progressLabel) {
        progressLabel.textContent = 'Uploading…';
      }
    }

    function syncInputFiles() {
      if (typeof DataTransfer === 'undefined') {
        return false;
      }
      var dt = new DataTransfer();
      selectedFiles.forEach(function (file) {
        dt.items.add(file);
      });
      input.files = dt.files;
      return selectedFiles.length > 0;
    }

    function appendFilesToFormData(formData) {
      if (!selectedFiles.length) {
        return;
      }
      selectedFiles.forEach(function (file) {
        formData.append('attachments[]', file, file.name);
      });
    }

    function renderFileList() {
      if (!fileList) {
        return;
      }
      fileList.innerHTML = '';
      if (!selectedFiles.length) {
        fileList.hidden = true;
        return;
      }
      fileList.hidden = false;
      selectedFiles.forEach(function (file, index) {
        var li = document.createElement('li');
        li.className = 'mw-attachment-file-item d-flex align-items-center gap-2 py-1';
        li.innerHTML =
          '<i class="bi ' + fileIconClass(file) + ' text-muted"></i>' +
          '<span class="mw-attachment-file-name text-truncate flex-grow-1" title="' + file.name.replace(/"/g, '&quot;') + '">' + file.name + '</span>' +
          '<span class="mw-attachment-file-size text-muted small text-nowrap">' + formatBytes(file.size) + '</span>' +
          '<button type="button" class="btn btn-sm btn-link text-danger p-0 mw-attachment-file-remove" data-index="' + index + '" title="Remove" aria-label="Remove"><i class="bi bi-x-lg"></i></button>';
        fileList.appendChild(li);
      });
      fileList.querySelectorAll('.mw-attachment-file-remove').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var idx = parseInt(btn.getAttribute('data-index'), 10);
          if (!isNaN(idx)) {
            selectedFiles.splice(idx, 1);
            if (!selectedFiles.length) {
              input.value = '';
            } else {
              syncInputFiles();
            }
            renderFileList();
          }
        });
      });
    }

    function addFiles(fileListObj) {
      hideError();
      resetProgress();
      if (!fileListObj || !fileListObj.length) {
        return;
      }
      var next = selectedFiles.slice();
      for (var i = 0; i < fileListObj.length; i++) {
        var file = fileListObj[i];
        if (maxBytes > 0 && file.size > maxBytes) {
          showError('"' + file.name + '" is too large. Maximum size is ' + formatBytes(maxBytes) + '.');
          return;
        }
        next.push(file);
      }
      if (next.length > maxFiles) {
        showError('You can attach up to ' + maxFiles + ' files at once.');
        return;
      }
      selectedFiles = next;
      syncInputFiles();
      renderFileList();
    }

    input.addEventListener('change', function () {
      if (!input.files || !input.files.length) {
        return;
      }
      addFiles(input.files);
    });

    widget._mwResetProgress = resetProgress;
    widget._mwSetProgress = setProgress;
    widget._mwShowError = showError;
    widget._mwSyncInputFiles = syncInputFiles;
    widget._mwAppendFilesToFormData = appendFilesToFormData;
    widget._mwHasFiles = function () {
      return selectedFiles.length > 0;
    };
  }

  function appendFormFields(formData, form, skipInput) {
    Array.prototype.forEach.call(form.elements, function (el) {
      if (!el.name || el.disabled) {
        return;
      }
      if (el === skipInput || el.type === 'file') {
        return;
      }
      if (el.type === 'submit' || el.type === 'button') {
        return;
      }
      if (el.type === 'checkbox' || el.type === 'radio') {
        if (el.checked) {
          formData.append(el.name, el.value);
        }
        return;
      }
      if (el.tagName === 'SELECT') {
        if (el.multiple) {
          Array.prototype.forEach.call(el.selectedOptions, function (opt) {
            formData.append(el.name, opt.value);
          });
        } else if (el.selectedIndex >= 0) {
          formData.append(el.name, el.options[el.selectedIndex].value);
        }
        return;
      }
      formData.append(el.name, el.value);
    });
  }

  function buildUploadFormData(form, widget) {
    var fileInput = widget ? widget.querySelector('.mw-attachment-input') : null;
    var formData = new FormData();
    appendFormFields(formData, form, fileInput);
    if (widget && widget._mwHasFiles && widget._mwHasFiles() && widget._mwAppendFilesToFormData) {
      widget._mwAppendFilesToFormData(formData);
    }
    return formData;
  }

  function inputHasFiles(fileInput) {
    return !!(fileInput && fileInput.files && fileInput.files.length > 0);
  }

  function submitWithProgress(form, widget, submitBtn) {
    var xhr = new XMLHttpRequest();
    var formData = buildUploadFormData(form, widget);

    widget._mwSetProgress(0, 'Uploading…');
    if (submitBtn) {
      submitBtn.disabled = true;
    }

    xhr.open('POST', form.getAttribute('action') || form.action, true);
    xhr.withCredentials = true;

    xhr.upload.addEventListener('progress', function (e) {
      if (e.lengthComputable && e.total > 0) {
        var pct = Math.min(99, Math.round((e.loaded / e.total) * 100));
        widget._mwSetProgress(pct, 'Uploading…');
      } else if (e.loaded > 0) {
        widget._mwSetProgress(null, 'Uploading… ' + formatBytes(e.loaded));
      }
    });

    xhr.upload.addEventListener('load', function () {
      widget._mwSetProgress(100, 'Processing…');
    });

    xhr.addEventListener('load', function () {
      if (xhr.status === 403) {
        widget._mwResetProgress();
        if (submitBtn) {
          submitBtn.disabled = false;
        }
        var body = xhr.responseText || '';
        if (body.indexOf('Upload too large') !== -1) {
          widget._mwShowError('File is too large for the server. Reduce file size or increase PHP upload limits.');
        } else {
          widget._mwShowError('Upload was denied. Refresh the page and try again.');
        }
        return;
      }

      if (xhr.status === 413) {
        widget._mwResetProgress();
        if (submitBtn) {
          submitBtn.disabled = false;
        }
        widget._mwShowError('File is too large for the server. Reduce file size or increase PHP upload limits.');
        return;
      }

      if (xhr.status >= 200 && xhr.status < 400) {
        widget._mwSetProgress(100, 'Done — redirecting…');
        var target = xhr.responseURL || form.getAttribute('action') || form.action;
        window.location.href = target;
        return;
      }

      widget._mwResetProgress();
      if (submitBtn) {
        submitBtn.disabled = false;
      }
      widget._mwShowError('Upload failed (HTTP ' + xhr.status + '). Please try again.');
    });

    xhr.addEventListener('error', function () {
      widget._mwResetProgress();
      if (submitBtn) {
        submitBtn.disabled = false;
      }
      widget._mwShowError('Network error during upload. Check your connection and try again.');
    });

    xhr.addEventListener('abort', function () {
      widget._mwResetProgress();
      if (submitBtn) {
        submitBtn.disabled = false;
      }
    });

    xhr.send(formData);
  }

  function initForms() {
    document.querySelectorAll('.mw-upload-form').forEach(function (form) {
      if (form._mwUploadBound) {
        return;
      }
      form._mwUploadBound = true;

      form.addEventListener('submit', function (ev) {
        var tinymceId = form.getAttribute('data-tinymce-id');
        if (tinymceId && window.tinymce && tinymce.get(tinymceId)) {
          tinymce.get(tinymceId).save();
        }

        var widget = form.querySelector('.mw-attachment-widget');
        var fileInput = widget ? widget.querySelector('.mw-attachment-input') : null;
        var submitBtn = form.querySelector('[type="submit"]');
        var hasSelected = widget && widget._mwHasFiles && widget._mwHasFiles();

        if (hasSelected && widget._mwSyncInputFiles) {
          widget._mwSyncInputFiles();
        }

        var hasOnInput = inputHasFiles(fileInput);
        var needsXhr = hasSelected && !hasOnInput;

        if (hasSelected || hasOnInput) {
          if (widget && widget._mwSetProgress) {
            widget._mwSetProgress(needsXhr ? 0 : null, 'Uploading…');
          }
        }

        if (needsXhr) {
          ev.preventDefault();
          submitWithProgress(form, widget, submitBtn);
          return;
        }

        if (submitBtn) {
          submitBtn.disabled = true;
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.mw-attachment-widget').forEach(initWidget);
    initForms();
  });
})();
