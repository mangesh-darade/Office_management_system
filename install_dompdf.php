<?php
/**
 * Manual DomPDF Installer
 * This script downloads and installs dompdf when composer is not available
 */

// Create the vendor directory structure
$vendorDir = APPPATH . '../vendor';
$dompdfDir = $vendorDir . '/dompdf/dompdf';

if (!is_dir($vendorDir)) {
    mkdir($vendorDir, 0777, true);
}

if (!is_dir($dompdfDir)) {
    mkdir($dompdfDir, 0777, true);
    
    // Download dompdf (you would need to manually download this)
    // For now, let's create a placeholder autoloader
    
    $autoloadContent = '<?php
// Simple autoloader for dompdf
spl_autoload_register(function ($class) {
    $prefix = "Dompdf\\";
    $base_dir = __DIR__ . "/dompdf/dompdf/src/";
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace("\\", "/", $relative_class) . ".php";
    
    if (file_exists($file)) {
        require $file;
    }
});
';
    
    file_put_contents($vendorDir . '/autoload.php', $autoloadContent);
    
    echo "DomPDF directory structure created. Please download dompdf manually and extract to vendor/dompdf/dompdf/\n";
} else {
    echo "DomPDF directory already exists.\n";
}
