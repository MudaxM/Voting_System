<?php
echo "<h1>Voting System Setup Wizard</h1>";

// Step 1: Check PHP version
if (version_compare(PHP_VERSION, '7.4.0') < 0) {
    die("Error: PHP 7.4 or higher is required. Current: " . PHP_VERSION);
}
echo "✓ PHP version OK<br>";

// Step 2: Check required extensions
$required_extensions = ['mysqli', 'pdo_mysql', 'gd', 'mbstring', 'json'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        echo "✗ Missing extension: $ext<br>";
    } else {
        echo "✓ Extension $ext loaded<br>";
    }
}

// Step 3: Check directory permissions
$directories = ['uploads', 'uploads/candidates', 'assets', 'includes'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✓ Created directory: $dir<br>";
        } else {
            echo "✗ Failed to create: $dir<br>";
        }
    } else {
        echo "✓ Directory exists: $dir<br>";
    }
}

// Step 4: Test database connection
$config_file = 'includes/config.php';
if (!file_exists($config_file)) {
    // Create default config
    $config_content = '<?php
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "cvoting_system");
define("SITE_URL", "http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/");
?>';
    file_put_contents($config_file, $config_content);
    echo "✓ Created config file<br>";
}

// Step 5: Create .htaccess for security
$htaccess = 'RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?/$1 [L]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"

# Protect sensitive files
<FilesMatch "^(config|setup|test)\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Disable directory browsing
Options -Indexes';

file_put_contents('.htaccess', $htaccess);
echo "✓ Created .htaccess file<br>";

echo "<h2>Setup Complete!</h2>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='admin/'>Go to Admin Panel</a></p>";
echo "<p>Default Admin: admin@voting.edu / Admin@123</p>";
?>