(function ($) {
  'use strict';

  var cfg = window.SB_CONFIG || {};
  var state = {
    plan: cfg.defaultPlan || 'Essential',
    industry: cfg.defaultIndustry || 'Retail',
    catalog: null,
    qty: {},
    search: '',
    includedChecked: {},
    includedExpanded: false,
    includedSectionOpen: true,
    planSectionOpen: true,
    industrySectionOpen: true,
    addonsSectionOpen: true
  };

  var planDescriptions = {
    Essential: 'Core POS, inventory, billing, and reports for single-location businesses.',
    Professional: 'Multi-location, GST, and franchise-ready operations.',
    Enterprise: 'Advanced CRM, production, webshop, and omni-channel capabilities.',
    ElintOm: 'Specialized modules for services, AMC, and site management.'
  };

  var industryIcons = {
    'Retail': 'bi-shop',
    'Food & Beverages': 'bi-cup-hot',
    'Manufacturing': 'bi-gear-wide-connected',
    'Clothing': 'bi-bag',
    'Services & AMC': 'bi-tools'
  };

  var INCLUDED_PREVIEW_ROWS = 2;
  var INCLUDED_COLS = 6;
  var INCLUDED_PREVIEW_COUNT = INCLUDED_PREVIEW_ROWS * INCLUDED_COLS;

  function formatMoney(n) {
    var val = parseFloat(n) || 0;
    return '₹ ' + val.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function calcLine(item, qty) {
    qty = parseInt(qty, 10) || 0;
    var perItemSetup = parseFloat(item.per_item_set_up_charges) || 0;
    var commonSetup = parseFloat(item.common_set_up_fees) || 0;
    var perMonth = parseFloat(item.per_item_per_month_maintenances) || 0;
    var setup = commonSetup + (perItemSetup * qty);
    var monthly = perMonth * qty;
    return { setup: setup, monthly: monthly };
  }

  function defaultQty(item) {
    if (item.item_unit) {
      return 1;
    }
    return 0;
  }

  function setLoading(on) {
    $('#sb-included-wrap, #sb-addons-body').closest('.sb-section-scroll, .sb-section-addons').toggleClass('opacity-50', on);
    $('#sb-loading').toggleClass('d-none', !on);
  }

  function initIncludedChecked(catalog) {
    state.includedChecked = {};
    var modules = catalog.included_by_module || {};
    Object.keys(modules).forEach(function (mod) {
      (modules[mod] || []).forEach(function (item) {
        state.includedChecked[item.id] = true;
      });
    });
  }

  function includedSelectedCount() {
    var n = 0;
    Object.keys(state.includedChecked).forEach(function (id) {
      if (state.includedChecked[id]) {
        n += 1;
      }
    });
    return n;
  }

  function updateIncludedViewAllButton(total) {
    var $btn = $('#sb-included-view-all');
    var $section = $('#sb-included-section');
    if (!$btn.length) {
      return;
    }
    if (!state.includedSectionOpen || total <= INCLUDED_PREVIEW_COUNT) {
      $btn.addClass('d-none');
      if (total <= INCLUDED_PREVIEW_COUNT) {
        $section.removeClass('sb-included-list-expanded');
      }
      return;
    }
    $btn.removeClass('d-none');
    if (state.includedExpanded) {
      $btn.text('Show less');
      $section.addClass('sb-included-list-expanded');
    } else {
      $btn.text('View all (' + total + ')');
      $section.removeClass('sb-included-list-expanded');
    }
    $btn.attr('title', includedSelectedCount() + ' / ' + total + ' selected');
    $('.sb-main').toggleClass('sb-main-included-list-expanded', !!(state.includedExpanded && state.includedSectionOpen));
  }

  function setSectionCollapsed(sectionSelector, toggleSelector, bodySelector, isOpen, collapsedClass) {
    collapsedClass = collapsedClass || 'sb-section-collapsed';
    var $section = $(sectionSelector);
    var $toggle = $(toggleSelector);
    var $body = $(bodySelector);
    $section.toggleClass(collapsedClass, !isOpen);
    $toggle.attr('aria-expanded', isOpen ? 'true' : 'false');
    $toggle.find('.sb-section-chevron i')
      .toggleClass('bi-chevron-down', isOpen)
      .toggleClass('bi-chevron-right', !isOpen);
    $body.toggleClass('d-none', !isOpen);
  }

  function updatePlanSectionCollapse() {
    setSectionCollapsed('#sb-plan-section', '#sb-plan-toggle', '#sb-plan-body', state.planSectionOpen);
  }

  function updateIndustrySectionCollapse() {
    setSectionCollapsed('#sb-industry-section', '#sb-industry-toggle', '#sb-industry-body', state.industrySectionOpen);
  }

  function updateIncludedSectionCollapse() {
    setSectionCollapsed('#sb-included-section', '#sb-included-toggle', '#sb-included-body', state.includedSectionOpen, 'sb-included-collapsed');
    if (!state.includedSectionOpen) {
      $('.sb-main').removeClass('sb-main-included-list-expanded');
    }
    if (state.catalog) {
      updateIncludedViewAllButton(state.catalog.included_count || 0);
    } else {
      $('#sb-included-view-all').toggleClass('d-none', !state.includedSectionOpen);
    }
  }

  function updateAddonsSectionCollapse() {
    setSectionCollapsed('#sb-addons-section', '#sb-addons-toggle', '#sb-addons-body', state.addonsSectionOpen);
  }

  function isDesktopLayout() {
    return window.matchMedia('(min-width: 1200px)').matches;
  }

  function applyDesktopSectionDefaults() {
    if (isDesktopLayout()) {
      state.planSectionOpen = false;
      state.industrySectionOpen = false;
    }
  }

  function planDisplayName(plan) {
    var p = String(plan || '').trim();
    if (/^elintom$/i.test(p)) {
      return 'ElintOm';
    }
    return 'ElintOm ' + p;
  }

  function renderPlans() {
    var html = '';
    (cfg.plans || []).forEach(function (plan) {
      var active = plan === state.plan ? ' active' : '';
      var rec = plan === 'Professional' ? ' recommended' : '';
      var desc = planDescriptions[plan] || 'Subscription plan for your business.';
      html += '<div class="sb-plan-card' + active + rec + '" data-plan="' + escapeHtml(plan) + '">';
      html += '<div class="sb-plan-name">' + escapeHtml(planDisplayName(plan)) + '</div>';
      html += '<div class="sb-plan-desc">' + escapeHtml(desc) + '</div>';
      html += '<div class="sb-plan-meta"><span class="sb-plan-count" data-plan-count="' + escapeHtml(plan) + '">—</span> included features</div>';
      html += '</div>';
    });
    $('#sb-plan-grid').html(html || '<div class="sb-empty">No plans in catalog.</div>');
  }

  function renderIndustries() {
    var html = '';
    (cfg.industries || []).forEach(function (industry) {
      var active = industry === state.industry ? ' active' : '';
      var icon = industryIcons[industry] || 'bi-building';
      html += '<button type="button" class="sb-industry-btn' + active + '" data-industry="' + escapeHtml(industry) + '">';
      html += '<i class="bi ' + icon + '"></i> ' + escapeHtml(industry);
      html += '</button>';
    });
    $('#sb-industry-row').html(html || '<div class="sb-empty">No industries in catalog.</div>');
  }

  function renderIncluded() {
    var catalog = state.catalog;
    if (!catalog) {
      return;
    }

    $('#sb-included-title').text('Included in ' + planDisplayName(state.plan));

    var modules = catalog.included_by_module || {};
    var keys = Object.keys(modules).sort();
    if (!keys.length) {
      $('#sb-included-wrap').html('<div class="sb-empty">No included features for this plan and industry.</div>');
      updateIncludedViewAllButton(0);
      return;
    }

    var allItems = [];
    keys.forEach(function (mod) {
      (modules[mod] || []).forEach(function (item) {
        allItems.push(item);
      });
    });

    var visibleItems = allItems;
    if (!state.includedExpanded && allItems.length > INCLUDED_PREVIEW_COUNT) {
      visibleItems = allItems.slice(0, INCLUDED_PREVIEW_COUNT);
    }

    updateIncludedViewAllButton(allItems.length);

    var html = '<div class="sb-included-cards">';
    visibleItems.forEach(function (item) {
      var checked = !!state.includedChecked[item.id];
      var uncheckedClass = checked ? '' : ' is-unchecked';
      var iconClass = checked ? 'bi-check-circle-fill' : 'bi-circle';
      html += '<label class="sb-included-card' + uncheckedClass + '" data-id="' + item.id + '">';
      html += '<input type="checkbox" class="sb-included-cb visually-hidden" data-id="' + item.id + '"' + (checked ? ' checked' : '') + '>';
      html += '<span class="sb-included-check-icon" aria-hidden="true"><i class="bi ' + iconClass + '"></i></span>';
      html += '<span class="sb-included-card-text">' + escapeHtml(item.feature) + '</span>';
      html += '</label>';
    });
    html += '</div>';
    $('#sb-included-wrap').html(html);
  }

  function filteredChargeable() {
    var list = (state.catalog && state.catalog.chargeable) ? state.catalog.chargeable.slice() : [];
    var q = (state.search || '').toLowerCase();
    if (!q) {
      return list;
    }
    return list.filter(function (item) {
      return (item.module || '').toLowerCase().indexOf(q) !== -1
        || (item.feature || '').toLowerCase().indexOf(q) !== -1
        || (item.details || '').toLowerCase().indexOf(q) !== -1;
    });
  }

  function renderAddons() {
    var list = filteredChargeable();
    if (!list.length) {
      $('#sb-addons-rows').html('<tr><td colspan="10" class="text-center text-muted py-4">No chargeable add-ons for this plan and industry.</td></tr>');
      return;
    }

    var html = '';
    list.forEach(function (item, idx) {
      var qty = state.qty[item.id];
      if (qty === undefined) {
        qty = defaultQty(item);
        state.qty[item.id] = qty;
      }
      var line = calcLine(item, qty);
      var moduleTitle = escapeHtml(item.module);
      var featureTitle = escapeHtml(item.feature);
      html += '<tr data-id="' + item.id + '">';
      html += '<td class="sb-col-index">' + (idx + 1) + '</td>';
      html += '<td><span class="sb-cell-text" title="' + moduleTitle + '">' + moduleTitle + '</span></td>';
      html += '<td class="sb-col-feature-cell">';
      html += '<span class="sb-cell-feature" title="' + featureTitle + '">' + featureTitle + '</span>';
      if (item.details) {
        var detailsTitle = escapeHtml(item.details);
        html += '<span class="sb-cell-feature-detail" title="' + detailsTitle + '">' + detailsTitle + '</span>';
      }
      html += '</td>';
      html += '<td class="text-end sb-money d-none d-xl-table-cell">' + (item.per_item_set_up_charges ? formatMoney(item.per_item_set_up_charges) : '—') + '</td>';
      html += '<td class="d-none d-lg-table-cell"><span class="sb-cell-text" title="' + escapeHtml(item.item_unit || '') + '">' + escapeHtml(item.item_unit || '—') + '</span></td>';
      html += '<td class="sb-col-qty-cell"><div class="sb-qty-control">';
      html += '<button type="button" class="sb-qty-minus" data-id="' + item.id + '">−</button>';
      html += '<input type="number" min="0" step="1" class="sb-qty-input" data-id="' + item.id + '" value="' + qty + '">';
      html += '<button type="button" class="sb-qty-plus" data-id="' + item.id + '">+</button>';
      html += '</div></td>';
      html += '<td class="text-end sb-money d-none d-md-table-cell">' + (item.common_set_up_fees ? formatMoney(item.common_set_up_fees) : '—') + '</td>';
      html += '<td class="text-end sb-money d-none d-md-table-cell">' + (item.per_item_per_month_maintenances ? formatMoney(item.per_item_per_month_maintenances) : '—') + '</td>';
      html += '<td class="text-end sb-money sb-line-setup">' + formatMoney(line.setup) + '</td>';
      html += '<td class="text-end sb-money sb-line-monthly">' + formatMoney(line.monthly) + '</td>';
      html += '</tr>';
    });
    $('#sb-addons-rows').html(html);
  }

  function renderSummary() {
    var totals = buildQuoteTotals();
    $('#sb-summary-plan').text(planDisplayName(state.plan));
    $('#sb-summary-industry').text(state.industry);

    function linesHtml(lines) {
      if (!lines.length) {
        return '<div class="text-muted small">None selected</div>';
      }
      return lines.map(function (l) {
        return '<div class="sb-summary-line"><span>' + escapeHtml(l.label) + '</span><span>' + formatMoney(l.amount) + '</span></div>';
      }).join('');
    }

    $('#sb-setup-lines').html(linesHtml(totals.setupLines));
    $('#sb-monthly-lines').html(linesHtml(totals.monthlyLines));
    $('#sb-total-setup').text(formatMoney(totals.totalSetup));
    $('#sb-total-monthly').text(formatMoney(totals.totalMonthly));
    $('#sb-net-setup').text(formatMoney(totals.totalSetup));
    $('#sb-net-monthly').text(formatMoney(totals.totalMonthly));
  }

  function buildQuoteTotals() {
    var setupLines = [];
    var monthlyLines = [];
    var totalSetup = 0;
    var totalMonthly = 0;

    var list = (state.catalog && state.catalog.chargeable) ? state.catalog.chargeable : [];
    list.forEach(function (item) {
      var qty = parseInt(state.qty[item.id], 10) || 0;
      if (qty <= 0) {
        return;
      }
      var line = calcLine(item, qty);
      if (line.setup > 0) {
        setupLines.push({ label: item.feature, amount: line.setup, qty: qty, module: item.module });
        totalSetup += line.setup;
      }
      if (line.monthly > 0) {
        monthlyLines.push({ label: item.feature, amount: line.monthly, qty: qty, module: item.module });
        totalMonthly += line.monthly;
      }
    });

    return {
      setupLines: setupLines,
      monthlyLines: monthlyLines,
      totalSetup: totalSetup,
      totalMonthly: totalMonthly
    };
  }

  function buildQuotePayload() {
    var totals = buildQuoteTotals();
    var included = [];
    var modules = (state.catalog && state.catalog.included_by_module) ? state.catalog.included_by_module : {};
    Object.keys(modules).forEach(function (mod) {
      (modules[mod] || []).forEach(function (item) {
        if (state.includedChecked[item.id]) {
          included.push({ module: mod, feature: item.feature });
        }
      });
    });

    return {
      plan: state.plan,
      plan_display: planDisplayName(state.plan),
      industry: state.industry,
      setup_lines: totals.setupLines,
      monthly_lines: totals.monthlyLines,
      total_setup: totals.totalSetup,
      total_monthly: totals.totalMonthly,
      included: included
    };
  }

  function submitQuoteForm(url, target) {
    if (!state.catalog) {
      alert('Please wait for the catalog to load.');
      return;
    }
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    if (target) {
      form.target = target;
    }
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'quote_json';
    input.value = JSON.stringify(buildQuotePayload());
    form.appendChild(input);
    if (typeof window.appendCsrfToForm === 'function') {
      window.appendCsrfToForm(form);
    }
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
  }

  function loadCatalog() {
    setLoading(true);
    $.getJSON(cfg.catalogUrl, { plan: state.plan, industry: state.industry })
      .done(function (res) {
        if (!res || !res.ok) {
          alert((res && res.error) ? res.error : 'Unable to load catalog.');
          return;
        }
        state.catalog = res;
        state.qty = {};
        state.search = '';
        state.includedExpanded = false;
        initIncludedChecked(res);
        $('#sb-addon-search').val('');
        $('.sb-plan-count[data-plan-count="' + state.plan + '"]').text(res.included_count);
        setLoading(false);
        renderIncluded();
        renderAddons();
        renderSummary();
      })
      .fail(function () {
        setLoading(false);
        alert('Unable to load subscription catalog.');
      });
  }

  function bindEvents() {
    $(document).on('click', '.sb-plan-card', function () {
      state.plan = $(this).data('plan');
      $('.sb-plan-card').removeClass('active');
      $(this).addClass('active');
      loadCatalog();
    });

    $(document).on('click', '.sb-industry-btn', function () {
      state.industry = $(this).data('industry');
      $('.sb-industry-btn').removeClass('active');
      $(this).addClass('active');
      loadCatalog();
    });

    $(document).on('change', '.sb-included-cb', function () {
      var id = $(this).data('id');
      var checked = $(this).is(':checked');
      state.includedChecked[id] = checked;
      var $card = $(this).closest('.sb-included-card');
      $card.toggleClass('is-unchecked', !checked);
      $card.find('.sb-included-check-icon i')
        .toggleClass('bi-check-circle-fill', checked)
        .toggleClass('bi-circle', !checked);
      if (state.catalog) {
        var total = state.catalog.included_count || 0;
        updateIncludedViewAllButton(total);
      }
    });

    $(document).on('click', '#sb-included-view-all', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!state.catalog) {
        return;
      }
      state.includedExpanded = !state.includedExpanded;
      renderIncluded();
      if (state.includedExpanded) {
        window.requestAnimationFrame(function () {
          var el = document.getElementById('sb-included-section');
          if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        });
      }
    });

    $('#sb-plan-toggle').on('click', function () {
      state.planSectionOpen = !state.planSectionOpen;
      updatePlanSectionCollapse();
    });

    $('#sb-industry-toggle').on('click', function () {
      state.industrySectionOpen = !state.industrySectionOpen;
      updateIndustrySectionCollapse();
    });

    $('#sb-included-toggle').on('click', function () {
      state.includedSectionOpen = !state.includedSectionOpen;
      updateIncludedSectionCollapse();
    });

    $('#sb-addons-toggle').on('click', function () {
      state.addonsSectionOpen = !state.addonsSectionOpen;
      updateAddonsSectionCollapse();
    });

    $(document).on('click', '.sb-qty-minus', function () {
      var id = $(this).data('id');
      var val = Math.max(0, (parseInt(state.qty[id], 10) || 0) - 1);
      state.qty[id] = val;
      renderAddons();
      renderSummary();
    });

    $(document).on('click', '.sb-qty-plus', function () {
      var id = $(this).data('id');
      var val = (parseInt(state.qty[id], 10) || 0) + 1;
      state.qty[id] = val;
      renderAddons();
      renderSummary();
    });

    $(document).on('change input', '.sb-qty-input', function () {
      var id = $(this).data('id');
      var val = Math.max(0, parseInt($(this).val(), 10) || 0);
      state.qty[id] = val;
      $(this).val(val);
      renderAddons();
      renderSummary();
    });

    $('#sb-addon-search').on('input', function () {
      state.search = $(this).val();
      renderAddons();
    });

    $('#sb-clear-all').on('click', function () {
      state.qty = {};
      Object.keys(state.includedChecked).forEach(function (id) {
        state.includedChecked[id] = false;
      });
      renderIncluded();
      renderAddons();
      renderSummary();
    });

    $('#sb-preview-quote').on('click', function () {
      submitQuoteForm(cfg.previewQuoteUrl, '_blank');
    });

    $('#sb-download-quote').on('click', function () {
      submitQuoteForm(cfg.downloadQuoteUrl, '_self');
    });
  }

  $(function () {
    renderPlans();
    renderIndustries();
    bindEvents();
    applyDesktopSectionDefaults();
    updatePlanSectionCollapse();
    updateIndustrySectionCollapse();
    updateIncludedSectionCollapse();
    updateAddonsSectionCollapse();
    loadCatalog();
  });
})(jQuery);
