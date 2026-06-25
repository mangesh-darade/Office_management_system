<?php $this->load->view('partials/header', ['title' => 'Knowledge Base']); ?>
<div class="container-fluid py-3">
<?php
$can_add = function_exists('has_module_access') && (has_module_access('knowledge_base_add') || has_module_access('knowledge_base'));
$actions = $can_add ? '<a class="btn btn-primary btn-sm" href="'.site_url('knowledge-base/create').'"><i class="bi bi-plus-lg me-1"></i>New Article</a>' : '';
$this->load->view('partials/oms_page_head', ['title' => 'Knowledge Base', 'icon' => 'bi-journal-bookmark', 'subtitle' => 'Share guides and how-to articles', 'actions_html' => $actions]);
?>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Author</th><th></th></tr></thead><tbody>
<?php if (empty($rows)): ?><tr><td colspan="5" class="text-muted text-center">No articles yet.</td></tr><?php else: foreach ($rows as $r): ?>
<tr><td><a href="<?php echo site_url('knowledge-base/view/'.$r->id); ?>"><?php echo esc_view($r->title); ?></a></td><td><?php echo esc_view($r->category ?: '—'); ?></td><td><?php echo esc_view($r->status); ?></td><td><?php echo esc_view($r->author_name ?: ''); ?></td><td><?php if (function_exists('has_module_access') && (has_module_access('knowledge_base_edit') || has_module_access('knowledge_base'))): ?><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('knowledge-base/edit/'.$r->id); ?>">Edit</a><?php else: ?>—<?php endif; ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div></div></div>
<?php $this->load->view('partials/footer'); ?>
