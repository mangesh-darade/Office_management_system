<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>404 Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; color: #343a40; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .error-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-card { max-width: 500px; width: 100%; border: none; border-radius: 1rem; }
        .error-code { font-size: 6rem; font-weight: 800; color: #dee2e6; line-height: 1; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="card error-card shadow-lg p-5 text-center">
            <div class="error-code mb-4">404</div>
            <h1 class="h3 mb-3">Page Not Found</h1>
            <p class="text-muted mb-4 small">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
            <div class="d-grid gap-2">
                <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-primary">
                    <i class="bi bi-house-door me-2"></i>Back to Dashboard
                </a>
                <button onclick="history.back()" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Go Back
                </button>
            </div>
        </div>
    </div>
</body>
</html>
