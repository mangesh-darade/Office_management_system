  <?php if (isset($__with_sidebar) && $__with_sidebar): ?>
    </main>
  </div>
</div>
<?php else: ?>
  <?php if (isset($__full_width) && !($__full_width)): ?>
    </div>
  <?php endif; ?>
</main>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.7/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/responsive.bootstrap5.min.js"></script>
<script src="<?php echo base_url('assets/js/app.js'); ?>"></script>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function(){
      navigator.serviceWorker.register('<?php echo base_url('assets/pwa/sw.js'); ?>').catch(function(e){console.warn('SW reg failed', e)})
    })
  }
  // Auto-init DataTables on tables with .datatable class
  // Note: DataTables is initialized in assets/js/app.js to avoid double initialization.
 </script>
</body>
</html>
