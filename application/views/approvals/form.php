<?php $this->load->view('partials/header', ['title' => 'Manage Approval Flow']); ?>
<div class="oms-form-compact">

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3><?php echo isset($flow) ? 'Edit Flow' : 'Create New Flow'; ?></h3>
            </div>
            <div class="card-body">
                <form action="<?php echo site_url('approvals/save'); ?>" method="post" id="flowForm">
                    <input type="hidden" name="id" value="<?php echo isset($flow) ? $flow->id : ''; ?>">

                    <div class="mb-3">
                        <label class="form-label">Module (e.g., leave, expense)</label>
                        <select name="module" class="form-select" required>
                            <option value="leave" <?php echo isset($flow) && $flow->module == 'leave' ? 'selected' : ''; ?>>Leave</option>
                            <option value="expense" <?php echo isset($flow) && $flow->module == 'expense' ? 'selected' : ''; ?>>Expense</option>
                            <option value="payroll" <?php echo isset($flow) && $flow->module == 'payroll' ? 'selected' : ''; ?>>Payroll</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Flow Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo isset($flow) ? esc_view($flow->name) : ''; ?>" required placeholder="e.g. Standard Leave Approval">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo !isset($flow) || $flow->is_active ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active (Set as default flow for this module)</label>
                    </div>

                    <h5 class="mt-4 mb-3">Approval Steps</h5>
                    <div id="steps-container">
                        <?php 
                        $steps = isset($flow) && isset($flow->steps) ? $flow->steps : [];
                        if (empty($steps)) {
                            // Default step 1
                            $steps[] = (object)['approver_type' => 'manager', 'approver_value' => ''];
                        }
                        foreach ($steps as $index => $step): 
                        ?>
                        <div class="card mb-3 step-card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="badge bg-secondary step-number">Step <?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="col">
                                        <select class="form-select step-type" name="step_type[]" onchange="toggleValueInput(this)">
                                            <option value="manager" <?php echo $step->approver_type == 'manager' ? 'selected' : ''; ?>>Direct Manager</option>
                                            <option value="department_head" <?php echo $step->approver_type == 'department_head' ? 'selected' : ''; ?>>Department Head</option>
                                            <option value="role" <?php echo $step->approver_type == 'role' ? 'selected' : ''; ?>>Specific Role (e.g. HR)</option>
                                            <option value="user" <?php echo $step->approver_type == 'user' ? 'selected' : ''; ?>>Specific User</option>
                                        </select>
                                    </div>
                                    <div class="col step-value-col" style="<?php echo in_array($step->approver_type, ['role', 'user']) ? '' : 'display:none'; ?>">
                                        <input type="number" class="form-control" name="step_value[]" value="<?php echo $step->approver_value; ?>" placeholder="ID (Role or User)">
                                        <small class="text-muted">Enter ID</small>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-danger btn-sm remove-step-btn" <?php echo count($steps) == 1 ? 'disabled' : ''; ?> onclick="removeStep(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-4">
                        <button type="button" class="btn btn-outline-secondary" onclick="addStep()">
                            <i class="bi bi-plus-circle me-1"></i>Add Step
                        </button>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?php echo site_url('approvals'); ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Workflow</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleValueInput(select) {
        const row = select.closest('.row');
        const valueCol = row.querySelector('.step-value-col');
        const hiddenTypes = ['manager', 'department_head'];
        if (hiddenTypes.includes(select.value)) {
            valueCol.style.display = 'none';
        } else {
            valueCol.style.display = '';
        }
    }

    function removeStep(btn) {
        const stepCard = btn.closest('.step-card');
        const container = document.getElementById('steps-container');
        if (container.children.length > 1) {
            stepCard.remove();
            renumberSteps();
        } else {
            alert('At least one step is required.');
        }
    }

    function addStep() {
        const container = document.getElementById('steps-container');
        const stepCount = container.children.length + 1;
        const template = `
            <div class="card mb-3 step-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="badge bg-secondary step-number">Step ${stepCount}</span>
                        </div>
                        <div class="col">
                            <select class="form-select step-type" name="step_type[]" onchange="toggleValueInput(this)">
                                <option value="manager">Direct Manager</option>
                                <option value="department_head">Department Head</option>
                                <option value="role">Specific Role (e.g. HR)</option>
                                <option value="user">Specific User</option>
                            </select>
                        </div>
                        <div class="col step-value-col" style="display:none">
                            <input type="number" class="form-control" name="step_value[]" placeholder="ID (Role or User)">
                            <small class="text-muted">Enter ID</small>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-danger btn-sm remove-step-btn" onclick="removeStep(this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', template);
        renumberSteps(); // Ensure remove buttons are enabled correctly
    }

    function renumberSteps() {
        const container = document.getElementById('steps-container');
        const cards = container.querySelectorAll('.step-card');
        cards.forEach((card, index) => {
            card.querySelector('.step-number').textContent = 'Step ' + (index + 1);
            const removeBtn = card.querySelector('.remove-step-btn');
            removeBtn.disabled = cards.length === 1;
        });
    }
</script>

</div>
<?php $this->load->view('partials/footer'); ?>
