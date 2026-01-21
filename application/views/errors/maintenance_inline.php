<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
// Maintenance content to be shown within normal layout (header, sidebar, footer)
$title = 'System Under Maintenance';
// Ensure with_sidebar is set to show sidebar in maintenance page
$with_sidebar = isset($with_sidebar) ? $with_sidebar : true;
$hide_navbar = isset($hide_navbar) ? $hide_navbar : false;
$this->load->view('partials/header', ['title' => $title, 'with_sidebar' => $with_sidebar, 'hide_navbar' => $hide_navbar]); 
?>

<style>
    /* Police Siren Effect - More Visible */
    .siren-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
    }
    
    .siren-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    .siren-red {
        background: rgba(220, 53, 69, 0.08);
        animation: siren-red 0.8s infinite alternate;
    }
    
    .siren-blue {
        background: rgba(13, 110, 253, 0.08);
        animation: siren-blue 0.8s infinite alternate;
        animation-delay: 0.4s;
    }
    
    @keyframes siren-red {
        0% { opacity: 0.05; }
        100% { opacity: 0.15; }
    }
    
    @keyframes siren-blue {
        0% { opacity: 0.05; }
        100% { opacity: 0.15; }
    }
    
    /* Main Wrapper */
    .maintenance-wrapper {
        position: relative;
        z-index: 10;
        min-height: calc(100vh - 180px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 40px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        width: 100%;
    }
    
    /* Maintenance Card */
    .maintenance-card {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12), 0 8px 24px rgba(0, 0, 0, 0.08);
        padding: 25px 35px;
        text-align: center;
        animation: slide-up 0.8s ease-out;
        border: 1px solid rgba(102, 126, 234, 0.1);
        width: 100%;
        margin: 0 auto;
        position: relative;
        overflow: visible;
        max-height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    
    .maintenance-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #667eea 100%);
        background-size: 200% 100%;
        animation: shimmer 3s linear infinite;
    }
    
    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    @keyframes slide-up {
        from {
            opacity: 0;
            transform: translateY(40px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    /* Icon */
    .maintenance-icon {
        font-size: 55px;
        margin-bottom: 10px;
        animation: rotate 3s linear infinite;
        color: #667eea;
        display: inline-block;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* Title */
    .maintenance-title {
        font-size: 1.65rem;
        font-weight: 700;
        margin-bottom: 6px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
    }
    
    /* Subtitle */
    .maintenance-subtitle {
        font-size: 0.95rem;
        color: #718096;
        margin-bottom: 15px;
        font-weight: 400;
    }
    
    /* Message Box */
    .maintenance-message {
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        border-left: 4px solid #667eea;
        padding: 12px 16px;
        border-radius: 10px;
        margin: 12px 0;
        text-align: left;
        font-size: 0.9rem;
        color: #2d3748;
        line-height: 1.4;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }
    
    .maintenance-message i {
        color: #667eea;
        font-size: 1.2rem;
        vertical-align: middle;
    }
    
    /* Progress Bar */
    .progress-container {
        margin-top: 15px;
        padding: 8px;
        background: #f7fafc;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-bar {
        height: 8px;
        border-radius: 12px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        animation: progress 2.5s ease-in-out infinite;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }
    
    @keyframes progress {
        0%, 100% {
            width: 35%;
            margin-left: 0;
        }
        50% {
            width: 75%;
            margin-left: 12.5%;
        }
    }
    
    /* Info Items */
    .maintenance-info {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #4a5568;
        font-size: 0.85rem;
        padding: 8px 12px;
        background: #f7fafc;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .info-item:hover {
        background: #edf2f7;
        transform: translateY(-2px);
    }
    
    .info-item i {
        font-size: 1.1rem;
        color: #667eea;
    }
    
    /* Contact Section */
    .contact-section {
        margin-top: 15px;
        padding: 15px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 12px;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }
    
    .contact-section h6 {
        color: #1e40af;
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }
    
    .contact-section p {
        color: #475569;
        font-size: 0.85rem;
        line-height: 1.4;
        margin-bottom: 12px;
    }
    
    .contact-section .btn {
        font-weight: 500;
        padding: 8px 20px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .contact-section .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .maintenance-card {
            padding: 35px 25px;
            width: 100%;
        }
        
        .maintenance-wrapper {
            padding: 20px;
        }
        
        .maintenance-title {
            font-size: 1.75rem;
        }
        
        .maintenance-icon {
            font-size: 60px;
        }
        
        .maintenance-info {
            gap: 15px;
            flex-direction: column;
        }
        
        .info-item {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<!-- Police Siren Effect -->
<div class="siren-container">
    <div class="siren-overlay siren-red"></div>
    <div class="siren-overlay siren-blue"></div>
</div>

<div class="maintenance-wrapper">
    <div class="maintenance-card">
        <div class="maintenance-icon">
            <i class="bi bi-gear-fill"></i>
        </div>
        
        <h1 class="maintenance-title">
            <i class="bi bi-tools me-2"></i>System Under Maintenance
        </h1>
        
        <p class="maintenance-subtitle">
            We're working hard to improve your experience
        </p>
        
        <div class="maintenance-message">
            <i class="bi bi-info-circle-fill text-primary me-2"></i>
            <?php echo htmlspecialchars(isset($message) ? $message : 'The system is currently under maintenance. Please try again later.'); ?>
        </div>
        
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>
        
        <div class="maintenance-info">
            <div class="info-item">
                <i class="bi bi-clock-history"></i>
                <span>Maintenance in Progress</span>
            </div>
            <div class="info-item">
                <i class="bi bi-shield-check"></i>
                <span>Your Data is Safe</span>
            </div>
            <div class="info-item">
                <i class="bi bi-arrow-repeat"></i>
                <span>Please Check Back Soon</span>
            </div>
        </div>
        
        <div class="contact-section">
            <h6 class="d-flex align-items-center">
                <i class="bi bi-person-badge me-2"></i>Need Immediate Assistance?
            </h6>
            <p class="mb-0">If you need urgent access or have questions, please contact the system administrator.</p>
            <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
                <a href="mailto:<?php echo htmlspecialchars(isset($company_email) ? $company_email : 'admin@example.com'); ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-envelope-fill me-1"></i> Email Admin
                </a>
                <a href="tel:<?php echo htmlspecialchars(isset($company_phone) ? $company_phone : '+1234567890'); ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-telephone-fill me-1"></i> Call Admin
                </a>
            </div>
        </div>
        
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
