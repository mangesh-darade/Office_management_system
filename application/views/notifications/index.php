<?php $this->load->view('partials/header', ['title' => 'Notifications']); ?>



<div class="d-flex justify-content-between align-items-center mb-4">

  <div>

    <h1 class="h3 mb-2">

      <i class="bi bi-bell me-2"></i>Notifications

    </h1>

    <p class="text-muted mb-0">Click a notification to open it. Unread items are marked read automatically.</p>

  </div>

  <div class="d-flex gap-2">

    <button type="button" class="btn btn-outline-primary" id="markAllRead" <?php echo $unread_count > 0 ? '' : 'disabled'; ?>>

      <i class="bi bi-check-all me-1"></i>Mark All Read

    </button>

    <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-outline-secondary">

      <i class="bi bi-arrow-left me-1"></i>Back

    </a>

  </div>

</div>



<ul class="nav nav-tabs mb-3" id="notificationFilterTabs">

  <li class="nav-item">

    <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="<?php echo site_url('notifications?filter=all'); ?>">

      All

    </a>

  </li>

  <li class="nav-item">

    <a class="nav-link <?php echo $filter === 'unread' ? 'active' : ''; ?>" href="<?php echo site_url('notifications?filter=unread'); ?>">

      Unread

      <span class="badge bg-danger js-unread-badge" <?php echo $unread_count > 0 ? '' : 'style="display:none"'; ?>><?php echo (int) $unread_count; ?></span>

    </a>

  </li>

  <li class="nav-item">

    <a class="nav-link <?php echo $filter === 'read' ? 'active' : ''; ?>" href="<?php echo site_url('notifications?filter=read'); ?>">

      Read

    </a>

  </li>

</ul>



<div class="card">

  <div class="card-body p-0">

    <?php if (empty($notifications)): ?>

      <div class="text-center py-5">

        <i class="bi bi-bell-slash" style="font-size: 3rem; color: #ccc;"></i>

        <p class="text-muted mt-3">No notifications found</p>

      </div>

    <?php else: ?>

      <div class="list-group list-group-flush" id="notificationList">

        <?php foreach ($notifications as $notification): ?>

        <?php

          $is_read = (int) $notification->is_read === 1;

          $action_url = !empty($notification->action_url) ? (string) $notification->action_url : '';

          $icon_class = 'bi-info-circle text-info';

          switch ($notification->type) {

              case 'success':

                  $icon_class = 'bi-check-circle text-success';

                  break;

              case 'warning':

                  $icon_class = 'bi-exclamation-triangle text-warning';

                  break;

              case 'error':

                  $icon_class = 'bi-x-circle text-danger';

                  break;

          }

        ?>

        <div class="list-group-item notification-item <?php echo $is_read ? '' : 'unread'; ?> notification-row"

             data-id="<?php echo (int) $notification->id; ?>"

             data-read="<?php echo $is_read ? '1' : '0'; ?>"

             data-url="<?php echo esc_view($action_url); ?>"

             role="button"

             tabindex="0">

          <div class="d-flex w-100 align-items-start">

            <div class="me-3 flex-shrink-0">

              <i class="bi <?php echo esc_view($icon_class); ?>" style="font-size: 1.5rem;"></i>

            </div>

            <div class="flex-grow-1 min-w-0">

              <div class="d-flex w-100 justify-content-between gap-2">

                <h6 class="mb-1 <?php echo $is_read ? '' : 'fw-bold'; ?>"><?php echo esc_view($notification->title); ?></h6>

                <small class="text-muted text-nowrap">

                  <?php

                  $time_ago = time() - strtotime($notification->created_at);

                  if ($time_ago < 60) {

                      echo 'Just now';

                  } elseif ($time_ago < 3600) {

                      echo floor($time_ago / 60) . ' min ago';

                  } elseif ($time_ago < 86400) {

                      echo floor($time_ago / 3600) . ' hours ago';

                  } else {

                      echo date('M d, Y', strtotime($notification->created_at));

                  }

                  ?>

                </small>

              </div>

              <p class="mb-1 text-break"><?php echo esc_view($notification->message); ?></p>

              <?php if ($notification->module): ?>

              <small class="text-muted">

                <i class="bi bi-tag me-1"></i><?php echo esc_view(ucfirst($notification->module)); ?>

              </small>

              <?php endif; ?>

            </div>

            <div class="ms-2 flex-shrink-0 notification-row-actions">

              <button type="button" class="btn btn-sm btn-outline-danger delete-notification" data-id="<?php echo (int) $notification->id; ?>" title="Delete">

                <i class="bi bi-trash"></i>

              </button>

            </div>

          </div>

        </div>

        <?php endforeach; ?>

      </div>

    <?php endif; ?>

  </div>

</div>



<style>

.notification-item.unread {

  background-color: #f8f9fa;

  border-left: 3px solid #0d6efd;

}



.notification-row {

  cursor: pointer;

  transition: background-color 0.2s;

}



.notification-row:hover {

  background-color: #f1f3f5;

}



.notification-row:focus {

  outline: 2px solid rgba(13, 110, 253, 0.35);

  outline-offset: -2px;

}

</style>



<script>

document.addEventListener('DOMContentLoaded', function() {

    var markAllBtn = document.getElementById('markAllRead');

    var unreadBadges = document.querySelectorAll('.js-unread-badge');



    function updateUnreadBadges(count) {

        unreadBadges.forEach(function(badge) {

            if (count > 0) {

                badge.textContent = count;

                badge.style.display = '';

            } else {

                badge.style.display = 'none';

            }

        });

        if (markAllBtn) {

            markAllBtn.disabled = count <= 0;

        }

    }



    function postNotificationAction(url, done) {

        if (window.jQuery) {

            jQuery.ajax({

                url: url,

                method: 'POST',

                dataType: 'json',

                data: {

                    ci_csrf_token: (typeof window.getCsrfToken === 'function') ? window.getCsrfToken() : ''

                }

            }).done(function(data) {

                if (typeof done === 'function') {

                    done(data || {});

                }

            }).fail(function(xhr) {

                alert('Request failed. Please refresh the page and try again.');

            });

            return;

        }



        var token = (typeof window.getCsrfToken === 'function') ? window.getCsrfToken() : '';

        var body = 'ci_csrf_token=' + encodeURIComponent(token);

        fetch(url, {

            method: 'POST',

            headers: {

                'Content-Type': 'application/x-www-form-urlencoded',

                'X-Requested-With': 'XMLHttpRequest'

            },

            body: body

        })

        .then(function(r) { return r.json(); })

        .then(function(data) {

            if (typeof done === 'function') {

                done(data || {});

            }

        })

        .catch(function() {

            alert('Request failed. Please refresh the page and try again.');

        });

    }



    function markRowReadVisual(row) {

        row.classList.remove('unread');

        row.dataset.read = '1';

        var title = row.querySelector('h6');

        if (title) {

            title.classList.remove('fw-bold');

        }

    }



    function openNotificationRow(row) {

        var id = row.dataset.id;

        var url = row.dataset.url || '';

        var isRead = row.dataset.read === '1';



        function finishOpen() {

            markRowReadVisual(row);

            if (url) {

                window.location.href = url;

            }

        }



        if (isRead) {

            if (url) {

                window.location.href = url;

            }

            return;

        }



        postNotificationAction('<?php echo site_url('notifications/mark-read'); ?>/' + id, function(data) {

            if (typeof data.unread_count !== 'undefined') {

                updateUnreadBadges(parseInt(data.unread_count, 10) || 0);

            }

            finishOpen();

        });

    }



    document.querySelectorAll('.notification-row').forEach(function(row) {

        row.addEventListener('click', function(ev) {

            if (ev.target.closest('.notification-row-actions')) {

                return;

            }

            openNotificationRow(row);

        });

        row.addEventListener('keydown', function(ev) {

            if (ev.key === 'Enter' || ev.key === ' ') {

                ev.preventDefault();

                openNotificationRow(row);

            }

        });

    });



    if (markAllBtn) {

        markAllBtn.addEventListener('click', function() {

            if (!confirm('Mark all notifications as read?')) {

                return;

            }

            postNotificationAction('<?php echo site_url('notifications/mark-all-read'); ?>', function(data) {

                if (data.success || data.status === 'success') {

                    if (typeof data.unread_count !== 'undefined') {

                        updateUnreadBadges(parseInt(data.unread_count, 10) || 0);

                    }

                    window.location.href = '<?php echo site_url('notifications?filter=all'); ?>';

                    return;

                }

                alert(data.message || 'Could not mark all notifications as read.');

            });

        });

    }



    document.querySelectorAll('.delete-notification').forEach(function(btn) {

        btn.addEventListener('click', function(ev) {

            ev.preventDefault();

            ev.stopPropagation();

            if (!confirm('Delete this notification?')) {

                return;

            }

            var id = this.dataset.id;

            var row = this.closest('.notification-row');

            postNotificationAction('<?php echo site_url('notifications/delete'); ?>/' + id, function(data) {

                if (data.success || data.status === 'success') {

                    if (row) {

                        row.remove();

                    }

                    if (typeof data.unread_count !== 'undefined') {

                        updateUnreadBadges(parseInt(data.unread_count, 10) || 0);

                    } else {

                        location.reload();

                    }

                    return;

                }

                alert(data.message || 'Could not delete notification.');

            });

        });

    });

});

</script>



<?php $this->load->view('partials/footer'); ?>

