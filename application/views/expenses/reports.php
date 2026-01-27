<?php $this->load->view('partials/header', ['title' => 'Expense Reports', 'active' => 'expenses']); ?>

<div class="container-fluid p-0">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <a href="<?php echo site_url('expenses'); ?>" class="btn btn-outline-secondary me-3">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h5 class="mb-0 fw-bold">Expense Analytics</h5>
                        <p class="text-muted mb-0 small">Overview of company expenses</p>
                    </div>
                </div>
                <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Print Report
                </button>
            </div>
        </div>
    </div>

    <!-- Monthly Trend -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">Monthly Trend (Last 12 Months)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">Claims Count</th>
                                    <th class="text-end">Total Amount</th>
                                    <th style="width: 40%;">Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $max_total = 0;
                                foreach($monthly as $m) { $max_total = max($max_total, $m->total); }
                                foreach($monthly as $m): 
                                    $percent = $max_total > 0 ? ($m->total / $max_total) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($m->month . '-01')); ?></td>
                                    <td class="text-end"><?php echo $m->count; ?></td>
                                    <td class="text-end fw-bold"><?php echo number_format($m->total, 2); ?></td>
                                    <td class="align-middle">
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-primary" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- By Category -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">Expenses by Category</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($by_category as $c): ?>
                                <tr>
                                    <td>
                                        <span class="fw-medium text-dark"><?php echo htmlspecialchars($c->name); ?></span>
                                        <div class="small text-muted"><?php echo $c->count; ?> claims</div>
                                    </td>
                                    <td class="text-end fw-bold"><?php echo number_format($c->total, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- By User -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold">Top Spenders</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($by_user as $u): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width:24px;height:24px;font-size:10px;">
                                                <?php echo strtoupper(substr($u->username, 0, 1)); ?>
                                            </div>
                                            <span><?php echo htmlspecialchars($u->username); ?></span>
                                        </div>
                                        <div class="small text-muted ps-4 ms-1"><?php echo $u->count; ?> claims</div>
                                    </td>
                                    <td class="text-end fw-bold"><?php echo number_format($u->total, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
