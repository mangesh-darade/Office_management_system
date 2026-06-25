<?php $this->load->view('partials/header', ['title' => 'Coaching']); ?>
<?php $this->load->view('coaching/_subnav'); ?>

<div class="coaching-page">
  <div class="coaching-hero-banner">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h1 class="h4 fw-bold mb-1">Coaching Dashboard</h1>
        <p class="text-muted mb-0 small">Monitor clients, sessions, leads, and revenue at a glance.</p>
      </div>
      <div class="d-flex gap-2">
        <?php if ((isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && has_module_access('coaching_sessions'))): ?>
        <a class="btn btn-primary btn-sm hover-lift" href="<?php echo site_url('coaching-sessions/create'); ?>">
          <i class="bi bi-plus-circle me-1"></i> New Session
        </a>
        <?php endif; ?>
        <?php if ((isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && has_module_access('coaching_clients'))): ?>
        <a class="btn btn-outline-secondary btn-sm hover-lift bg-white" href="<?php echo site_url('coaching-clients/create'); ?>">
          <i class="bi bi-person-plus me-1"></i> Add Client
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <?php
    $cards = [
      ['Active clients', (int) $stats['active_clients'], 'bi-people-fill', 'metric-emerald', 'icon-wrapper-emerald'],
      ['Coaches', (int) $stats['coaches'], 'bi-person-workspace', 'metric-violet', 'icon-wrapper-violet'],
      ['Sessions this week', (int) $stats['sessions_this_week'], 'bi-calendar3', 'metric-primary', 'icon-wrapper-primary'],
      ['Open leads', (int) $stats['open_leads'], 'bi-funnel-fill', 'metric-cyan', 'icon-wrapper-cyan'],
      ['Revenue collected', '₹' . number_format((float) $stats['revenue_paid'], 2), 'bi-currency-rupee', 'metric-amber', 'icon-wrapper-amber'],
      ['Pending installments', (int) $stats['pending_installments'], 'bi-clock-history', 'metric-rose', 'icon-wrapper-rose'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-md-6 col-lg-4 col-xl-2">
      <div class="card coaching-metric-card shadow-soft <?php echo $c[3]; ?>">
        <div class="card-body">
          <div class="coaching-icon-wrapper <?php echo $c[4]; ?>">
            <i class="bi <?php echo $c[2]; ?>"></i>
          </div>
          <div>
            <div class="coaching-metric-title"><?php echo esc_view($c[0]); ?></div>
            <div class="coaching-metric-value"><?php echo esc_view((string) $c[1]); ?></div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="coaching-card">
    <div class="coaching-card-body d-flex align-items-start gap-3">
      <div class="coaching-icon-wrapper icon-wrapper-primary">
        <i class="bi bi-info-circle"></i>
      </div>
      <div>
        <h2 class="h6 fw-bold mb-1">Quick navigation</h2>
        <p class="text-muted mb-0 small">Use the tab bar above to manage coaches, clients, sessions, goals, leads, billing, WhatsApp CRM, and reports.</p>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
