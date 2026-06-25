<?php $this->load->view('partials/header', ['title' => $action==='edit' ? 'Edit Asset' : 'Add Asset']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo $action==='edit' ? 'Edit Asset' : 'Add Asset'; ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('assets-mgmt'); ?>">Back</a>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" data-validate="true">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required value="<?php echo isset($row->name)?esc_view($row->name):''; ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Category</label>
          <input type="text" name="category" class="form-control" value="<?php echo isset($row->category)?esc_view($row->category):''; ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Brand</label>
          <input type="text" name="brand" class="form-control" value="<?php echo isset($row->brand)?esc_view($row->brand):''; ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Model</label>
          <input type="text" name="model" class="form-control" value="<?php echo isset($row->model)?esc_view($row->model):''; ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Serial Number</label>
          <input type="text" name="serial_no" class="form-control" value="<?php echo isset($row->serial_no)?esc_view($row->serial_no):''; ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Asset Tag</label>
          <input type="text" name="asset_tag" class="form-control" value="<?php echo isset($row->asset_tag)?esc_view($row->asset_tag):''; ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">RAM</label>
          <input type="text" name="ram" class="form-control" placeholder="8 GB, 16 GB" value="<?php echo isset($row->ram)?esc_view($row->ram):''; ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">HDD / Storage</label>
          <input type="text" name="hdd" class="form-control" placeholder="512 GB SSD, 1 TB HDD" value="<?php echo isset($row->hdd)?esc_view($row->hdd):''; ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <?php $st = isset($row->status)?$row->status:'in_stock'; ?>
            <option value="in_stock" <?php echo $st==='in_stock'?'selected':''; ?>>In stock</option>
            <option value="assigned" <?php echo $st==='assigned'?'selected':''; ?>>Assigned</option>
            <option value="retired" <?php echo $st==='retired'?'selected':''; ?>>Retired</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Purchased On</label>
          <input type="date" name="purchased_on" class="form-control" value="<?php echo isset($row->purchased_on)?esc_view($row->purchased_on):''; ?>" />
        </div>
        <div class="col-md-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="3"><?php echo isset($row->notes)?esc_view($row->notes):''; ?></textarea>
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
