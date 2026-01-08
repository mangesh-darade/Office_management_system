<?php $this->load->view('partials/header', ['title' => 'Access Denied']); ?>

<style>
.permission-denied-container {
  min-height: 70vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.permission-denied-content {
  text-align: center;
  max-width: 500px;
  padding: 3rem 2rem;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.permission-emoji {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  display: block;
}

.permission-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary, #1f2937);
  margin-bottom: 1rem;
}

.permission-message {
  font-size: 1rem;
  color: var(--text-secondary, #6b7280);
  line-height: 1.6;
  margin-bottom: 2rem;
}

.permission-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-home {
  padding: 0.75rem 1.5rem;
  background: var(--primary-color, #3b82f6);
  color: white;
  border: none;
  border-radius: 6px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: background 0.2s;
}

.btn-home:hover {
  background: var(--primary-dark, #2563eb);
  color: white;
  text-decoration: none;
}

.btn-back {
  padding: 0.75rem 1.5rem;
  background: #f3f4f6;
  color: var(--text-primary, #1f2937);
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: background 0.2s;
}

.btn-back:hover {
  background: #e5e7eb;
  color: var(--text-primary, #1f2937);
  text-decoration: none;
}
</style>

<div class="permission-denied-container">
  <div class="permission-denied-content">
    <span class="permission-emoji">🚫</span>
    <h1 class="permission-title">Access Denied</h1>
    <p class="permission-message">
      You do not have permission to access this page.
    </p>
    <div class="permission-actions">
      <a href="<?php echo site_url('dashboard'); ?>" class="btn-home">
        <i class="bi bi-house"></i>
        Go to Dashboard
      </a>
      <a href="javascript:history.back()" class="btn-back">
        <i class="bi bi-arrow-left"></i>
        Go Back
      </a>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
