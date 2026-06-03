<?php
$seg1 = strtolower($this->uri->segment(1) ?: '');
$seg2 = strtolower($this->uri->segment(2) ?: 'index');
$links = [
    ['coaching', 'index', 'Dashboard', 'bi-grid'],
    ['coaching-coaches', 'index', 'Coaches', 'bi-person-workspace'],
    ['coaching-clients', 'index', 'Clients', 'bi-people'],
    ['coaching-sessions', 'index', 'Sessions', 'bi-calendar-event'],
    ['coaching-sessions', 'calendar', 'Calendar', 'bi-calendar3'],
    ['coaching-goals', 'index', 'Goals & Homework', 'bi-bullseye'],
    ['coaching-leads', 'index', 'Leads', 'bi-funnel'],
    ['coaching-leads', 'workshops', 'Workshops', 'bi-megaphone'],
    ['coaching-billing', 'index', 'Billing', 'bi-receipt'],
    ['coaching-billing', 'payouts', 'Coach Payouts', 'bi-cash'],
    ['coaching-resources', 'index', 'Resources', 'bi-folder'],
    ['coaching-whatsapp-crm', 'index', 'WhatsApp CRM', 'bi-whatsapp'],
    ['coaching-reports', 'index', 'Reports', 'bi-graph-up'],
    ['coaching-admin', 'index', 'Settings', 'bi-gear'],
];
?>
<nav class="coaching-subnav nav nav-pills flex-wrap gap-1 mb-3 small">
  <?php foreach ($links as $l):
    $active = ($seg1 === $l[0] && ($seg2 === $l[1] || ($l[1] === 'index' && ($seg2 === '' || $seg2 === 'index'))));
  ?>
  <a class="nav-link py-1 px-2 <?php echo $active ? 'active' : 'text-secondary'; ?>" href="<?php echo site_url($l[0] . ($l[1] !== 'index' ? '/' . $l[1] : '')); ?>">
    <i class="bi <?php echo $l[3]; ?> me-1"></i><?php echo htmlspecialchars($l[2]); ?>
  </a>
  <?php endforeach; ?>
</nav>
