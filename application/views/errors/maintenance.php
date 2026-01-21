<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - <?php echo isset($company_name) ? htmlspecialchars($company_name) : 'Office Management System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        
        /* Police Siren Effect */
        .siren-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
            animation: siren-pulse 1s infinite;
        }
        
        .siren-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .siren-red {
            background: rgba(220, 53, 69, 0.15);
            animation: siren-red 0.5s infinite alternate;
        }
        
        .siren-blue {
            background: rgba(13, 110, 253, 0.15);
            animation: siren-blue 0.5s infinite alternate;
            animation-delay: 0.25s;
        }
        
        @keyframes siren-red {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }
        
        @keyframes siren-blue {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }
        
        @keyframes siren-pulse {
            0%, 100% {
                filter: brightness(1);
            }
            50% {
                filter: brightness(1.2);
            }
        }
        
        /* Main Content */
        .maintenance-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 800px;
            padding: 20px;
        }
        
        .maintenance-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            text-align: center;
            animation: slide-up 0.6s ease-out;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        @keyframes slide-up {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .maintenance-icon {
            font-size: 80px;
            margin-bottom: 30px;
            animation: rotate 2s linear infinite;
            color: #667eea;
        }
        
        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        .maintenance-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .maintenance-subtitle {
            font-size: 1.3rem;
            color: #4a5568;
            margin-bottom: 30px;
            font-weight: 500;
        }
        
        .maintenance-message {
            background: #f7fafc;
            border-left: 4px solid #667eea;
            padding: 25px;
            border-radius: 10px;
            margin: 30px 0;
            text-align: left;
            font-size: 1.1rem;
            color: #2d3748;
            line-height: 1.8;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .maintenance-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4a5568;
            font-size: 1rem;
        }
        
        .info-item i {
            font-size: 1.3rem;
            color: #667eea;
        }
        
        .progress-container {
            margin-top: 30px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 10px;
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 10px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            animation: progress 2s ease-in-out infinite;
        }
        
        @keyframes progress {
            0%, 100% {
                width: 30%;
                margin-left: 0;
            }
            50% {
                width: 70%;
                margin-left: 15%;
            }
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: 0;
            overflow: hidden;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            border-radius: 50%;
            background: white;
            animation: float 15s infinite;
        }
        
        .shape:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 10%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
            }
            33% {
                transform: translateY(-30px) translateX(30px);
            }
            66% {
                transform: translateY(30px) translateX(-30px);
            }
        }
        
        @media (max-width: 768px) {
            .maintenance-card {
                padding: 40px 25px;
            }
            
            .maintenance-title {
                font-size: 2rem;
            }
            
            .maintenance-icon {
                font-size: 60px;
            }
            
            .maintenance-info {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Police Siren Effect -->
    <div class="siren-container">
        <div class="siren-overlay siren-red"></div>
        <div class="siren-overlay siren-blue"></div>
    </div>
    
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <!-- Main Content -->
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
                <?php echo htmlspecialchars($message); ?>
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
            
            <div class="alert alert-info d-flex align-items-start mt-4 mb-3" style="background: #e7f3ff; border-left: 4px solid #0d6efd; padding: 20px; border-radius: 8px;">
                <i class="bi bi-telephone-fill me-3" style="font-size: 1.5rem; color: #0d6efd; margin-top: 2px;"></i>
                <div>
                    <h6 class="fw-bold mb-2" style="color: #0d6efd;">Need Immediate Assistance?</h6>
                    <p class="mb-2" style="color: #2d3748; line-height: 1.6;">If you need urgent access to this module or have any questions, please contact the system administrator.</p>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="mailto:<?php echo htmlspecialchars(isset($company_email) ? $company_email : 'admin@example.com'); ?>" class="btn btn-sm btn-outline-primary" style="text-decoration: none;">
                        <i class="bi bi-envelope-fill me-1"></i> Email Admin
                    </a>
                    <a href="tel:<?php echo htmlspecialchars(isset($company_phone) ? $company_phone : '+1234567890'); ?>" class="btn btn-sm btn-outline-success" style="text-decoration: none;">
                        <i class="bi bi-telephone-fill me-1"></i> Call Admin
                    </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h6 class="fw-semibold mb-3" style="color: #4a5568;">
                    <i class="bi bi-compass me-2"></i>Navigate to Other Modules
                </h6>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <?php
                    // Helper function to generate URL if base_url is available
                    $url_base = isset($base_url) ? rtrim($base_url, '/') : '';
                    if (empty($url_base)) {
                        // Fallback: construct from current URL
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                        $script = dirname($_SERVER['SCRIPT_NAME']);
                        $url_base = $protocol . '://' . $host . rtrim($script, '/');
                    }
                    ?>
                    <a href="<?php echo $url_base; ?>/dashboard" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                    <a href="<?php echo $url_base; ?>/tasks" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-check2-square me-1"></i> Tasks
                    </a>
                    <a href="<?php echo $url_base; ?>/projects" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-kanban me-1"></i> Projects
                    </a>
                    <a href="<?php echo $url_base; ?>/attendance" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-clock-history me-1"></i> Attendance
                    </a>
                    <a href="<?php echo $url_base; ?>/leaves" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-calendar-x me-1"></i> Leaves
                    </a>
                    <a href="<?php echo $url_base; ?>/chats" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-chat-dots me-1"></i> Chats
                    </a>
                    <a href="<?php echo $url_base; ?>/announcements" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-megaphone me-1"></i> Announcements
                    </a>
                    <a href="<?php echo $url_base; ?>/reports" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-graph-up me-1"></i> Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
