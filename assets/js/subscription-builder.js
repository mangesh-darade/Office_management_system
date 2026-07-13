(function ($) {
  'use strict';

  var cfg = window.SB_CONFIG || {};
  var state = {
    plan: cfg.defaultPlan || 'Essential',
    industry: cfg.defaultIndustry || 'Retail',
    country: cfg.defaultCountry || 'India',
    countryMeta: cfg.defaultCountryMeta || null,
    catalog: null,
    qty: {},
    search: '',
    includedChecked: {},
    includedModuleExpanded: {},
    includedSectionOpen: true,
    planSectionOpen: true,
    industrySectionOpen: true,
    addonsSectionOpen: false,
    clientName: '',
    clientBusiness: '',
    setupDiscountPercent: 0,
    setupDiscountFlat: 0,
    setupGstPercent: 18,
    monthlyDiscountPercent: 0,
    monthlyDiscountFlat: 0,
    monthlyGstPercent: 18,
    showPayment: false
  };

  var planDescriptions = {
    Essential: 'Core POS, inventory, billing, and reports for single-location businesses.',
    Business: 'Essential features plus business operations for growing teams.',
    Professional: 'Essential and Business features with multi-location and GST readiness.',
    Enterprise: 'Full stack — includes Essential, Business, and Professional capabilities.'
  };

  var industryIcons = {
    'Retail': 'bi-shop',
    'Food & Beverages': 'bi-cup-hot',
    'Manufacturing': 'bi-gear-wide-connected',
    'Clothing': 'bi-bag',
    'Services & AMC': 'bi-tools'
  };

  var INLINE_FEATURE_PREVIEW = 3;

  var currencyLocaleMap = {
    INR: 'en-IN',
    AED: 'en-AE',
    SAR: 'en-SA',
    QAR: 'en-QA',
    OMR: 'en-OM',
    KWD: 'en-KW',
    BHD: 'en-BH',
    GBP: 'en-GB',
    USD: 'en-US'
  };

  var countryAliases = {
    uae: ['united arab emirates', 'ae'],
    'united arab emirates': ['uae', 'ae'],
    ae: ['uae', 'united arab emirates'],
    ksa: ['saudi arabia', 'sa'],
    'saudi arabia': ['ksa', 'sa'],
    sa: ['saudi arabia', 'ksa'],
    uk: ['united kingdom', 'gb'],
    'united kingdom': ['uk', 'gb'],
    gb: ['united kingdom', 'uk'],
    us: ['united states', 'usa'],
    usa: ['united states', 'us'],
    'united states': ['us', 'usa']
  };

  var currencyFallback = {
    india: { code: 'IN', mobile_code: '91', currency_code: 'INR', currency_symbol: '₹' },
    uae: { code: 'AE', mobile_code: '971', currency_code: 'AED', currency_symbol: 'AED' },
    'united arab emirates': { code: 'AE', mobile_code: '971', currency_code: 'AED', currency_symbol: 'AED' },
    'saudi arabia': { code: 'SA', mobile_code: '966', currency_code: 'SAR', currency_symbol: 'SAR' },
    qatar: { code: 'QA', mobile_code: '974', currency_code: 'QAR', currency_symbol: 'QAR' },
    oman: { code: 'OM', mobile_code: '968', currency_code: 'OMR', currency_symbol: 'OMR' },
    kuwait: { code: 'KW', mobile_code: '965', currency_code: 'KWD', currency_symbol: 'KWD' },
    bahrain: { code: 'BH', mobile_code: '973', currency_code: 'BHD', currency_symbol: 'BHD' },
    'united kingdom': { code: 'GB', mobile_code: '44', currency_code: 'GBP', currency_symbol: '£' },
    'united states': { code: 'US', mobile_code: '1', currency_code: 'USD', currency_symbol: '$' }
  };

  function countryNamesMatch(a, b) {
    var left = String(a || '').toLowerCase().trim();
    var right = String(b || '').toLowerCase().trim();
    if (!left || !right) {
      return false;
    }
    if (left === right) {
      return true;
    }
    var aliases = countryAliases[left];
    if (aliases && aliases.indexOf(right) !== -1) {
      return true;
    }
    aliases = countryAliases[right];
    if (aliases && aliases.indexOf(left) !== -1) {
      return true;
    }
    return false;
  }

  function getCurrencyFallback(name) {
    var key = String(name || '').toLowerCase().trim();
    if (currencyFallback[key]) {
      return currencyFallback[key];
    }
    var aliases = countryAliases[key];
    if (aliases) {
      for (var i = 0; i < aliases.length; i++) {
        if (currencyFallback[aliases[i]]) {
          return currencyFallback[aliases[i]];
        }
      }
    }
    return null;
  }

  function getCountryMeta(name) {
    var target = String(name || state.country || '').trim();
    var options = cfg.countryOptions || [];
    var found = null;
    options.forEach(function (item) {
      if (!item) {
        return;
      }
      if (countryNamesMatch(item.name, target)) {
        found = item;
        return;
      }
      if (item.code && countryNamesMatch(item.code, target)) {
        found = item;
      }
    });
    if (found) {
      return found;
    }
    if (state.countryMeta && countryNamesMatch(state.countryMeta.name, target)) {
      return state.countryMeta;
    }
    var fallback = getCurrencyFallback(target);
    if (fallback) {
      return {
        name: target || state.country || 'India',
        code: fallback.code || '',
        mobile_code: fallback.mobile_code || '',
        currency_code: fallback.currency_code || 'INR',
        currency_symbol: fallback.currency_symbol || '₹'
      };
    }
    return {
      name: target || state.country || 'India',
      code: '',
      mobile_code: '',
      currency_code: 'INR',
      currency_symbol: '₹'
    };
  }

  function syncCountryMeta(name) {
    var catalogName = name || state.country;
    state.countryMeta = getCountryMeta(catalogName);
    if (catalogName) {
      state.countryMeta.name = catalogName;
    }
  }

  function formatCurrencyLabel(meta) {
    meta = meta || getCountryMeta(state.country);
    var code = meta.currency_code || 'INR';
    var symbol = meta.currency_symbol || code;
    if (symbol === code) {
      return code;
    }
    return code + ' (' + symbol + ')';
  }

  function updateCurrencyDisplay() {
    var meta = getCountryMeta(state.country);
    var label = formatCurrencyLabel(meta);
    $('#sb-currency-display').text(label);
    $('#sb-summary-currency').text(label);
  }

  function applyPaymentViewMode() {
    var show = !!state.showPayment;
    $('.sb-page').toggleClass('sb-proposal-only', !show);
    $('#sb-show-payment-toggle').prop('checked', show);
    $('.sb-view-mode-option-feature').toggleClass('is-active', !show);
    $('.sb-view-mode-option-proposal').toggleClass('is-active', show);
    $('#sb-summary-title').text(show ? 'PROPOSAL SUMMARY' : 'FEATURE SUMMARY');
    updateAddonsLayoutState();
  }

  function formatMoney(n) {
    var meta = getCountryMeta(state.country);
    var val = parseFloat(n) || 0;
    var symbol = meta.currency_symbol || meta.currency_code || '₹';
    var locale = currencyLocaleMap[meta.currency_code] || 'en-IN';
    return symbol + ' ' + val.toLocaleString(locale, { minimumFractionDigits: 0, maximumFractionDigits: 0 });
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

  function updateIncludedCountBadge(total) {
    var $badge = $('#sb-included-count-badge');
    if (!$badge.length) {
      return;
    }
    if (!total) {
      $badge.addClass('d-none').text('');
      return;
    }
    $badge.removeClass('d-none').text(total + ' feature' + (total === 1 ? '' : 's'));
  }

  function syncIncludedCountBadge() {
    if (!state.catalog) {
      updateIncludedCountBadge(0);
      return;
    }
    updateIncludedCountBadge(state.catalog.included_count || 0);
  }

  function getModuleIcon(moduleName) {
    var mod = String(moduleName || '').toLowerCase();
    var rules = [
      { match: ['user', 'people', 'biller', 'customer', 'vendor', 'supplier'], icon: 'bi-people' },
      { match: ['product', 'inventory', 'stock', 'catalog', 'variant'], icon: 'bi-box-seam' },
      { match: ['tax', 'gst', 'tally'], icon: 'bi-percent' },
      { match: ['payment', 'deposit', 'wallet'], icon: 'bi-credit-card' },
      { match: ['sales', 'billing', 'invoice', 'pos'], icon: 'bi-receipt' },
      { match: ['report', 'analytics', 'dashboard'], icon: 'bi-bar-chart-line' },
      { match: ['location', 'geo', 'branch', 'multi location'], icon: 'bi-geo-alt' },
      { match: ['purchase', 'procurement'], icon: 'bi-cart-plus' },
      { match: ['manufactur', 'production', 'batch'], icon: 'bi-gear-wide-connected' },
      { match: ['loyalty', 'reward', 'promo'], icon: 'bi-gift' },
      { match: ['kitchen', 'food', 'recipe'], icon: 'bi-cup-hot' },
      { match: ['integration', 'api', 'export'], icon: 'bi-plug' },
      { match: ['security', 'permission', 'role'], icon: 'bi-shield-check' },
      { match: ['account', 'ledger', 'finance'], icon: 'bi-journal-text' },
      { match: ['service', 'amc', 'support'], icon: 'bi-tools' },
      { match: ['retail', 'store', 'shop'], icon: 'bi-shop' },
      { match: ['email', 'sms', 'notification', 'alert'], icon: 'bi-bell' },
      { match: ['expense', 'cost'], icon: 'bi-wallet2' },
      { match: ['hr', 'payroll', 'attendance', 'employee'], icon: 'bi-person-workspace' },
      { match: ['clothing', 'apparel'], icon: 'bi-bag' },
      { match: ['barcode', 'label'], icon: 'bi-upc-scan' },
      { match: ['delivery', 'logistic', 'dispatch'], icon: 'bi-truck' }
    ];
    var i;
    var j;
    for (i = 0; i < rules.length; i++) {
      for (j = 0; j < rules[i].match.length; j++) {
        if (mod.indexOf(rules[i].match[j]) !== -1) {
          return rules[i].icon;
        }
      }
    }
    return 'bi-grid';
  }

  function moduleKey(moduleName) {
    return String(moduleName || '').toLowerCase().trim();
  }

  function isModuleExpanded(moduleName) {
    return !!state.includedModuleExpanded[moduleKey(moduleName)];
  }

  function setModuleExpanded(moduleName, expanded) {
    state.includedModuleExpanded[moduleKey(moduleName)] = !!expanded;
  }

  function getIncludedModulesList(modules) {
    var result = [];
    Object.keys(modules || {}).forEach(function (mod) {
      result.push({ name: mod, items: modules[mod] || [] });
    });
    return result;
  }

  function syncIncludedCheckboxUi(id, checked) {
    state.includedChecked[id] = checked;
    $('.sb-included-cb[data-id="' + id + '"]').prop('checked', checked);
    $('.sb-included-item[data-id="' + id + '"], .sb-acc-inline-feat[data-id="' + id + '"]').each(function () {
      var $el = $(this);
      $el.toggleClass('is-unchecked', !checked);
      $el.find('.sb-included-check-icon i').toggleClass('bi-check-circle-fill', checked).toggleClass('bi-circle', !checked);
      $el.children('i.bi').toggleClass('bi-check-circle-fill', checked).toggleClass('bi-circle', !checked);
    });
    renderSummary();
  }

  function renderInlineFeatures(mod, items) {
    var preview = (items || []).slice(0, INLINE_FEATURE_PREVIEW);
    var remaining = Math.max(0, (items || []).length - INLINE_FEATURE_PREVIEW);
    var html = '<div class="sb-acc-inline">';
    preview.forEach(function (item, idx) {
      if (idx > 0) {
        html += '<span class="sb-acc-sep" aria-hidden="true">·</span>';
      }
      var checked = !!state.includedChecked[item.id];
      var uncheckedClass = checked ? '' : ' is-unchecked';
      var iconClass = checked ? 'bi-check-circle-fill' : 'bi-circle';
      html += '<label class="sb-acc-inline-feat' + uncheckedClass + '" data-id="' + item.id + '" title="' + escapeHtml(item.feature) + '">';
      html += '<input type="checkbox" class="sb-included-cb visually-hidden" data-id="' + item.id + '"' + (checked ? ' checked' : '') + '>';
      html += '<i class="bi ' + iconClass + '" aria-hidden="true"></i>';
      html += '<span>' + escapeHtml(item.feature) + '</span>';
      html += '</label>';
    });
    if (remaining > 0) {
      html += '<button type="button" class="sb-acc-more" data-module="' + escapeHtml(mod) + '" title="Show all features">+' + remaining + ' More</button>';
    }
    html += '</div>';
    return html;
  }

  function renderIncludedFeatureList(items) {
    var html = '<ul class="sb-acc-feature-list">';
    (items || []).forEach(function (item) {
      var checked = !!state.includedChecked[item.id];
      var uncheckedClass = checked ? '' : ' is-unchecked';
      var iconClass = checked ? 'bi-check-circle-fill' : 'bi-circle';
      html += '<li>';
      html += '<label class="sb-included-item' + uncheckedClass + '" data-id="' + item.id + '">';
      html += '<input type="checkbox" class="sb-included-cb visually-hidden" data-id="' + item.id + '"' + (checked ? ' checked' : '') + '>';
      html += '<span class="sb-included-check-icon" aria-hidden="true"><i class="bi ' + iconClass + '"></i></span>';
      html += '<span class="sb-included-item-text">' + escapeHtml(item.feature) + '</span>';
      html += '</label>';
      html += '</li>';
    });
    html += '</ul>';
    return html;
  }

  function renderIncludedAccordionRow(mod, items) {
    var isOpen = isModuleExpanded(mod);
    var icon = getModuleIcon(mod);
    var count = (items || []).length;
    var openClass = isOpen ? ' is-open' : '';
    var html = '<div class="sb-acc-item' + openClass + '" data-module="' + escapeHtml(mod) + '">';
    html += '<div class="sb-acc-header" data-module="' + escapeHtml(mod) + '">';
    html += '<div class="sb-acc-module-meta" role="button" tabindex="0" aria-expanded="' + (isOpen ? 'true' : 'false') + '" data-module="' + escapeHtml(mod) + '">';
    html += '<span class="sb-acc-chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>';
    html += '<span class="sb-acc-icon"><i class="bi ' + icon + '" aria-hidden="true"></i></span>';
    html += '<span class="sb-acc-name">' + escapeHtml(mod) + '</span>';
    html += '<span class="sb-acc-count-badge">' + count + '</span>';
    html += '</div>';
    if (!isOpen) {
      html += renderInlineFeatures(mod, items);
    }
    html += '</div>';
    html += '<div class="sb-acc-panel" role="region" aria-label="' + escapeHtml(mod) + ' features">';
    html += '<div class="sb-acc-panel-inner">';
    html += renderIncludedFeatureList(items);
    html += '</div></div></div>';
    return html;
  }

  function renderIncluded() {
    var catalog = state.catalog;
    if (!catalog) {
      return;
    }

    $('#sb-included-title').text('Included in ' + planDisplayName(state.plan));

    var modules = catalog.included_by_module || {};
    var filtered = getIncludedModulesList(modules);
    if (!filtered.length) {
      $('#sb-included-wrap').html('<div class="sb-empty">No included features for this plan and industry.</div>');
      syncIncludedCountBadge();
      return;
    }

    syncIncludedCountBadge();

    var html = '<div class="sb-feature-accordion">';
    filtered.forEach(function (entry) {
      html += renderIncludedAccordionRow(entry.name, entry.items);
    });
    html += '</div>';
    $('#sb-included-wrap').html(html);
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
    updateAddonsLayoutState();
  }

  function updateIndustrySectionCollapse() {
    setSectionCollapsed('#sb-industry-section', '#sb-industry-toggle', '#sb-industry-body', state.industrySectionOpen);
    updateAddonsLayoutState();
  }

  function updateIncludedSectionCollapse() {
    setSectionCollapsed('#sb-included-section', '#sb-included-toggle', '#sb-included-body', state.includedSectionOpen, 'sb-included-collapsed');
    updateAddonsLayoutState();
  }

  function updateAddonsSectionCollapse() {
    setSectionCollapsed('#sb-addons-section', '#sb-addons-toggle', '#sb-addons-body', state.addonsSectionOpen);
    updateAddonsLayoutState();
  }

  function updateAddonsLayoutState() {
    $('.sb-main').toggleClass('sb-addons-open', !!state.addonsSectionOpen);
  }

  function isDesktopLayout() {
    return window.matchMedia('(min-width: 1200px)').matches;
  }

  function applyDesktopSectionDefaults() {
    if (isDesktopLayout()) {
      state.planSectionOpen = false;
      state.industrySectionOpen = false;
      state.includedSectionOpen = false;
    }
  }

  function planDisplayName(plan) {
    return 'ElintOm ' + String(plan || '').trim();
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

  function renderCountries() {
    var html = '';
    var list = cfg.countryOptions && cfg.countryOptions.length ? cfg.countryOptions : [];
    if (!list.length) {
      list = (cfg.countries || ['India']).map(function (name) {
        return { name: name, currency_code: 'INR', currency_symbol: '₹' };
      });
    }
    list.forEach(function (item) {
      var name = item.name || item;
      var selected = name === state.country ? ' selected' : '';
      html += '<option value="' + escapeHtml(name) + '"' + selected + '>' + escapeHtml(name) + '</option>';
    });
    $('#sb-country-select').html(html);
    updateCurrencyDisplay();
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
      $('#sb-addons-rows').html('<tr><td colspan="10" class="text-center text-muted py-4">No chargeable add-ons for this plan, industry, and country.</td></tr>');
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
      html += '<td class="sb-col-qty-cell"><div class="sb-qty-control">';
      html += '<button type="button" class="sb-qty-minus" data-id="' + item.id + '">−</button>';
      html += '<input type="number" min="0" step="1" class="sb-qty-input" data-id="' + item.id + '" value="' + qty + '">';
      html += '<button type="button" class="sb-qty-plus" data-id="' + item.id + '">+</button>';
      html += '</div></td>';
      html += '<td class="sb-addon-col-unit d-none d-lg-table-cell"><span class="sb-cell-text" title="' + escapeHtml(item.item_unit || '') + '">' + escapeHtml(item.item_unit || '—') + '</span></td>';
      html += '<td class="text-end sb-money d-none d-xl-table-cell sb-payment-only">' + (item.per_item_set_up_charges ? formatMoney(item.per_item_set_up_charges) : '—') + '</td>';
      html += '<td class="text-end sb-money d-none d-md-table-cell sb-payment-only">' + (item.per_item_per_month_maintenances ? formatMoney(item.per_item_per_month_maintenances) : '—') + '</td>';
      html += '<td class="text-end sb-money d-none d-md-table-cell sb-payment-only">' + (item.common_set_up_fees ? formatMoney(item.common_set_up_fees) : '—') + '</td>';
      html += '<td class="text-end sb-money sb-line-setup sb-payment-only">' + formatMoney(line.setup) + '</td>';
      html += '<td class="text-end sb-money sb-line-monthly sb-payment-only">' + formatMoney(line.monthly) + '</td>';
      html += '</tr>';
    });
    $('#sb-addons-rows').html(html);
  }

  function normalizeGstPercent(val) {
    var gstPct = parseFloat(val) || 0;
    if ([0, 5, 18].indexOf(gstPct) === -1) {
      gstPct = 0;
    }
    return gstPct;
  }

  function calcSectionDiscount(subtotal, discountPct, discountFlat) {
    if (discountPct < 0) {
      discountPct = 0;
    }
    if (discountPct > 100) {
      discountPct = 100;
    }
    if (discountFlat < 0) {
      discountFlat = 0;
    }
    var pctAmt = Math.round(subtotal * discountPct) / 100;
    var total = pctAmt + discountFlat;
    if (total > subtotal) {
      total = subtotal;
    }
    return total;
  }

  function parseDiscountInput(raw) {
    var trimmed = String(raw || '').trim();
    if (!trimmed) {
      return { percent: 0, flat: 0 };
    }
    if (/%/.test(trimmed)) {
      var discountPct = parseFloat(trimmed.replace(/%/g, '').trim());
      if (isNaN(discountPct) || discountPct < 0) {
        discountPct = 0;
      }
      if (discountPct > 100) {
        discountPct = 100;
      }
      return { percent: discountPct, flat: 0 };
    }
    var discountFlat = parseFloat(trimmed);
    if (isNaN(discountFlat) || discountFlat < 0) {
      discountFlat = 0;
    }
    return { percent: 0, flat: discountFlat };
  }

  function syncDiscountStateFromInputs() {
    var setupParsed = parseDiscountInput($('#sb-setup-discount-input').val());
    state.setupDiscountPercent = setupParsed.percent;
    state.setupDiscountFlat = setupParsed.flat;
    var monthlyParsed = parseDiscountInput($('#sb-monthly-discount-input').val());
    state.monthlyDiscountPercent = monthlyParsed.percent;
    state.monthlyDiscountFlat = monthlyParsed.flat;
    state.setupGstPercent = normalizeGstPercent($('#sb-setup-gst-percent').val());
    state.monthlyGstPercent = normalizeGstPercent($('#sb-monthly-gst-percent').val());
  }

  function buildFinancialSummary(totals) {
    var setupSubtotal = totals.totalSetup;
    var monthlySubtotal = totals.totalMonthly;
    var setupDiscountPct = parseFloat(state.setupDiscountPercent) || 0;
    var setupDiscountFlat = parseFloat(state.setupDiscountFlat) || 0;
    var monthlyDiscountPct = parseFloat(state.monthlyDiscountPercent) || 0;
    var monthlyDiscountFlat = parseFloat(state.monthlyDiscountFlat) || 0;
    var setupGstPct = normalizeGstPercent(state.setupGstPercent);
    var monthlyGstPct = normalizeGstPercent(state.monthlyGstPercent);

    var discountSetup = calcSectionDiscount(setupSubtotal, setupDiscountPct, setupDiscountFlat);
    var discountMonthly = calcSectionDiscount(monthlySubtotal, monthlyDiscountPct, monthlyDiscountFlat);
    var setupAfterDiscount = Math.max(0, setupSubtotal - discountSetup);
    var monthlyAfterDiscount = Math.max(0, monthlySubtotal - discountMonthly);
    var gstSetup = Math.round(setupAfterDiscount * setupGstPct) / 100;
    var gstMonthly = Math.round(monthlyAfterDiscount * monthlyGstPct) / 100;
    var netSetup = setupAfterDiscount + gstSetup;
    var netMonthly = monthlyAfterDiscount + gstMonthly;

    return {
      setupSubtotal: setupSubtotal,
      monthlySubtotal: monthlySubtotal,
      setupDiscountPercent: setupDiscountPct,
      setupDiscountFlat: setupDiscountFlat,
      monthlyDiscountPercent: monthlyDiscountPct,
      monthlyDiscountFlat: monthlyDiscountFlat,
      discountSetup: discountSetup,
      discountMonthly: discountMonthly,
      setupGstPercent: setupGstPct,
      monthlyGstPercent: monthlyGstPct,
      gstSetup: gstSetup,
      gstMonthly: gstMonthly,
      netSetup: netSetup,
      netMonthly: netMonthly
    };
  }

  function renderSummary() {
    var totals = buildQuoteTotals();
    var financials = buildFinancialSummary(totals);
    $('#sb-summary-plan').text(planDisplayName(state.plan));
    $('#sb-summary-industry').text(state.industry);
    $('#sb-summary-country').text(state.country);

    function featureAddonLinesHtml(lines) {
      if (!lines.length) {
        return '<div class="text-muted small py-1">None selected</div>';
      }
      return lines.map(function (l) {
        var module = escapeHtml(l.module);
        var feature = escapeHtml(l.label);
        var unit = l.unit ? '<span class="sb-summary-addon-unit">' + escapeHtml(l.unit) + '</span>' : '';
        return '<div class="sb-summary-addon-line">' +
          '<div class="sb-summary-addon-label">' +
            '<span class="sb-summary-addon-module">' + module + '</span>' +
            '<span class="sb-summary-addon-feature">' + feature + '</span>' +
            unit +
          '</div>' +
          '<span class="sb-summary-addon-qty">Qty ' + l.qty + '</span>' +
        '</div>';
      }).join('');
    }

    var includedCount = 0;
    Object.keys(state.includedChecked || {}).forEach(function (id) {
      if (state.includedChecked[id]) {
        includedCount += 1;
      }
    });
    if (includedCount > 0) {
      $('#sb-included-summary-text').html(
        '<div class="sb-summary-included-count"><i class="bi bi-check-circle-fill me-1"></i>' +
        includedCount + ' feature' + (includedCount === 1 ? '' : 's') + ' included</div>'
      );
    } else {
      $('#sb-included-summary-text').html('<div class="text-muted small py-1">None for this plan</div>');
    }

    $('#sb-feature-addon-lines').html(featureAddonLinesHtml(buildFeatureAddonLines()));

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
    $('#sb-subtotal-setup').text(formatMoney(financials.setupSubtotal));
    $('#sb-subtotal-monthly').text(formatMoney(financials.monthlySubtotal));
    $('#sb-discount-setup').text('-' + formatMoney(financials.discountSetup));
    $('#sb-discount-monthly').text('-' + formatMoney(financials.discountMonthly));
    $('#sb-gst-setup').text(formatMoney(financials.gstSetup));
    $('#sb-gst-monthly').text(formatMoney(financials.gstMonthly));
    $('#sb-total-setup').text(formatMoney(financials.netSetup));
    $('#sb-total-monthly').text(formatMoney(financials.netMonthly));
    $('#sb-net-setup').text(formatMoney(financials.netSetup));
    $('#sb-net-monthly').text(formatMoney(financials.netMonthly));
  }

  function buildFeatureAddonLines() {
    var lines = [];
    var list = (state.catalog && state.catalog.chargeable) ? state.catalog.chargeable : [];
    list.forEach(function (item) {
      var qty = parseInt(state.qty[item.id], 10) || 0;
      if (qty <= 0) {
        return;
      }
      lines.push({
        module: item.module,
        label: item.feature,
        qty: qty,
        unit: item.item_unit || ''
      });
    });
    return lines;
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
        setupLines.push({ label: item.feature, amount: line.setup, qty: qty, module: item.module, unit: item.item_unit || '' });
        totalSetup += line.setup;
      }
      if (line.monthly > 0) {
        monthlyLines.push({ label: item.feature, amount: line.monthly, qty: qty, module: item.module, unit: item.item_unit || '' });
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
    var financials = buildFinancialSummary(totals);
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
      country: state.country,
      country_code: state.countryMeta ? state.countryMeta.code : '',
      mobile_code: state.countryMeta ? state.countryMeta.mobile_code : '',
      currency_code: state.countryMeta ? state.countryMeta.currency_code : 'INR',
      currency_symbol: state.countryMeta ? state.countryMeta.currency_symbol : '₹',
      client_name: state.clientName,
      client_business: state.clientBusiness,
      setup_discount_percent: financials.setupDiscountPercent,
      setup_discount_flat: financials.setupDiscountFlat,
      setup_gst_percent: financials.setupGstPercent,
      monthly_discount_percent: financials.monthlyDiscountPercent,
      monthly_discount_flat: financials.monthlyDiscountFlat,
      monthly_gst_percent: financials.monthlyGstPercent,
      discount_percent: 0,
      discount_flat: 0,
      gst_percent: financials.setupGstPercent,
      setup_lines: totals.setupLines,
      monthly_lines: totals.monthlyLines,
      total_setup: totals.totalSetup,
      total_monthly: totals.totalMonthly,
      net_setup: financials.netSetup,
      net_monthly: financials.netMonthly,
      included: included,
      addon_lines: buildFeatureAddonLines(),
      show_payment: !!state.showPayment
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

  function loadCatalog(options) {
    options = options || {};
    var preserveIncluded = !!options.preserveIncluded;
    var prevIncluded = preserveIncluded ? $.extend({}, state.includedChecked) : null;

    setLoading(true);
    $.getJSON(cfg.catalogUrl, { plan: state.plan, industry: state.industry, country: state.country, _: Date.now() })
      .done(function (res) {
        if (!res || !res.ok) {
          alert((res && res.error) ? res.error : 'Unable to load catalog.');
          return;
        }
        state.catalog = res;
        if (res.country) {
          state.country = res.country;
          $('#sb-country-select').val(state.country);
        }
        if (res.country_meta) {
          state.countryMeta = res.country_meta;
          if (res.country) {
            state.countryMeta.name = res.country;
          }
        } else {
          syncCountryMeta(state.country);
        }
        updateCurrencyDisplay();
        state.qty = {};
        state.search = '';
        if (!preserveIncluded) {
          state.includedModuleExpanded = {};
          initIncludedChecked(res);
        } else if (prevIncluded) {
          state.includedChecked = prevIncluded;
        }
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

    function toggleModuleRow(mod) {
      setModuleExpanded(mod, !isModuleExpanded(mod));
      renderIncluded();
    }

    $(document).on('change', '.sb-included-cb', function (e) {
      e.stopPropagation();
      var id = $(this).data('id');
      syncIncludedCheckboxUi(id, $(this).is(':checked'));
    });

    $(document).on('click', '.sb-acc-inline-feat, .sb-included-item', function (e) {
      e.stopPropagation();
    });

    $(document).on('click', '.sb-acc-more', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var mod = $(this).data('module');
      setModuleExpanded(mod, true);
      renderIncluded();
      window.requestAnimationFrame(function () {
        var $item = $('.sb-acc-item').filter(function () {
          return $(this).attr('data-module') === mod;
        });
        if ($item.length) {
          $item[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      });
    });

    $(document).on('click', '.sb-acc-chevron, .sb-acc-name, .sb-acc-icon, .sb-acc-module-meta', function (e) {
      if ($(e.target).closest('.sb-acc-more, .sb-acc-inline, .sb-included-cb, .sb-acc-inline-feat, .sb-included-item').length) {
        return;
      }
      e.stopPropagation();
      var mod = $(this).closest('.sb-acc-header').data('module');
      toggleModuleRow(mod);
    });

    $(document).on('keydown', '.sb-acc-module-meta', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        var mod = $(this).data('module');
        toggleModuleRow(mod);
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

    $('#sb-country-select').on('change', function () {
      state.country = $(this).val();
      syncCountryMeta(state.country);
      updateCurrencyDisplay();
      loadCatalog({ preserveIncluded: true });
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

    $('#sb-export-toggle').on('click', function (e) {
      e.stopPropagation();
      var $menu = $('#sb-export-menu');
      var isOpen = !$menu.hasClass('d-none');
      $menu.toggleClass('d-none', isOpen);
      $(this).attr('aria-expanded', isOpen ? 'false' : 'true');
      $(this).toggleClass('is-open', !isOpen);
    });

    $(document).on('click', function () {
      $('#sb-export-menu').addClass('d-none');
      $('#sb-export-toggle').attr('aria-expanded', 'false').removeClass('is-open');
    });

    $('.sb-export-option').on('click', function (e) {
      e.stopPropagation();
      var format = $(this).data('format');
      var urlMap = {
        pdf: cfg.downloadQuoteUrl,
        excel: cfg.downloadExcelUrl,
        doc: cfg.downloadDocUrl
      };
      if (!urlMap[format]) {
        return;
      }
      submitQuoteForm(urlMap[format], '_self');
      $('#sb-export-menu').addClass('d-none');
      $('#sb-export-toggle').attr('aria-expanded', 'false').removeClass('is-open');
    });

    $('#sb-client-name').on('input', function () {
      state.clientName = $(this).val();
    });

    $('#sb-client-business').on('input', function () {
      state.clientBusiness = $(this).val();
    });

    $('#sb-setup-discount-input, #sb-monthly-discount-input').on('change input', function () {
      syncDiscountStateFromInputs();
      renderSummary();
    });

    $('#sb-setup-gst-percent, #sb-monthly-gst-percent').on('change input', function () {
      syncDiscountStateFromInputs();
      renderSummary();
    });

    $('#sb-show-payment-toggle').on('change', function () {
      state.showPayment = $(this).is(':checked');
      applyPaymentViewMode();
    });

    $(window).on('resize.sbLayout', function () {
      updateAddonsLayoutState();
    });
  }

  $(function () {
    syncCountryMeta(state.country);
    renderPlans();
    renderIndustries();
    renderCountries();
    bindEvents();
    applyPaymentViewMode();
    applyDesktopSectionDefaults();
    updatePlanSectionCollapse();
    updateIndustrySectionCollapse();
    updateIncludedSectionCollapse();
    updateAddonsSectionCollapse();
    updateAddonsLayoutState();
    loadCatalog();
  });
})(jQuery);
