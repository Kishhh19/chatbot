<?php
/**
 * Setup Script - Run once to initialize the database
 * Access: http://localhost/chatbot/setup.php
 * DELETE this file after setup is complete!
 */

// Basic protection - only run in development or with token
$setupToken = 'CHANGE_THIS_TOKEN_BEFORE_RUNNING';
if (!empty($_GET['token']) && $_GET['token'] !== $setupToken) {
    http_response_code(403);
    die('Invalid setup token.');
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

$messages = [];
$errors   = [];

// Step 1: Test DB connection
try {
    $pdo = Database::getInstance();
    $messages[] = 'Database connection successful.';
} catch (Exception $e) {
    $errors[] = 'Database connection failed: ' . $e->getMessage();
}

// Step 2: Import schema
if (empty($errors)) {
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        $errors[] = 'Schema file not found: ' . $schemaFile;
    } else {
        try {
            $sql = file_get_contents($schemaFile);
            // Split by semicolon, skipping empty statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            $count = 0;
            foreach ($statements as $stmt) {
                if (!empty($stmt) && $stmt !== "\n") {
                    $pdo->exec($stmt);
                    $count++;
                }
            }
            $messages[] = "Schema imported ($count statements executed).";
        } catch (Exception $e) {
            // Some errors are expected (e.g., table already exists)
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $messages[] = 'Some tables already exist (this is fine on re-run).';
            } else {
                $errors[] = 'Schema import error: ' . $e->getMessage();
            }
        }
    }
}

// Step 3: Create uploads directory
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    if (mkdir($uploadDir, 0750, true)) {
        file_put_contents($uploadDir . '.htaccess', "Order Allow,Deny\nDeny from all\n");
        $messages[] = 'Uploads directory created.';
    } else {
        $errors[] = 'Could not create uploads directory. Create it manually: ' . $uploadDir;
    }
} else {
    $messages[] = 'Uploads directory exists.';
}

// Step 4: Create logs directory
$logDir = __DIR__ . '/logs/';
if (!is_dir($logDir)) {
    if (mkdir($logDir, 0750, true)) {
        file_put_contents($logDir . '.htaccess', "Order Allow,Deny\nDeny from all\n");
        $messages[] = 'Logs directory created.';
    } else {
        $errors[] = 'Could not create logs directory.';
    }
} else {
    $messages[] = 'Logs directory exists.';
}

// Step 5: Check .env
if (file_exists(__DIR__ . '/.env')) {
    $messages[] = '.env file found.';
    $apiKey = getenv('AI_API_KEY') ?: '';
    if (empty($apiKey) || $apiKey === 'your-api-key-here') {
        $errors[] = 'AI_API_KEY not set in .env file. Edit the .env file and add your key.';
    } else {
        $messages[] = 'AI API key detected.';
    }
} else {
    $errors[] = '.env file not found. Copy .env.example to .env and configure it.';
}

// Step 6: Check PHP extensions
$required = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring', 'zip'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        $messages[] = "PHP extension loaded: $ext";
    } else {
        $errors[] = "Missing PHP extension: $ext (enable in php.ini)";
    }
}

// Step 7: Check admin exists
if (empty($errors) || count(array_filter($errors, fn($e) => !str_contains($e, 'Missing') && !str_contains($e, 'failed')))) {
    try {
        $adminExists = admin_exists();
        if ($adminExists) {
            $messages[] = 'Admin account exists.';
        } else {
            $messages[] = 'No admin account yet. Register at login.php — first user becomes admin automatically.';
        }
    } catch (Exception $e) {
        $errors[] = 'Could not check admin: ' . $e->getMessage();
    }
}

$allGood = true;
foreach($errors as $err) {
    if (str_contains($err, 'failed') || str_contains($err, 'Missing') || str_contains($err, 'error')) {
        $allGood = false;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Setup – AI Chatbot Platform</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body style="display:flex;align-items:flex-start;justify-content:center;padding:40px 20px;min-height:100vh">
<div style="max-width:680px;width:100%">
  <div style="text-align:center;margin-bottom:32px">
    <h1 style="font-size:28px;font-weight:700">AI Chatbot Platform Setup</h1>
    <p style="color:var(--text-secondary);margin-top:8px">System initialization check</p>
  </div>

  <div class="card mb-4">
    <div class="card-header"><span class="card-title">Setup Results</span></div>
    <?php foreach($messages as $msg): ?>
    <div style="padding:8px 0;font-size:14px;border-bottom:1px solid var(--border);color:var(--success)"><?= $msg ?></div>
    <?php endforeach; ?>
    <?php foreach($errors as $err): ?>
    <div style="padding:8px 0;font-size:14px;border-bottom:1px solid var(--border);color:var(--danger)"><?= $err ?></div>
    <?php endforeach; ?>
  </div>

  <?php if ($allGood): ?>
  <div class="alert alert-success">
    <strong>Setup complete!</strong> Your AI Chatbot Platform is ready.
    <strong>Delete this setup.php file now for security.</strong>
  </div>
  <div class="flex gap-3 mt-3">
    <a href="login.php" class="btn btn-primary">Go to Login</a>
    <a href="admin/dashboard.php" class="btn btn-secondary">Admin Dashboard</a>
  </div>
  <?php else: ?>
  <div class="alert alert-error">
    Some issues need to be resolved before the app can run.
  </div>
  <?php endif; ?>

  <div class="card mt-4">
    <div class="card-header"><span class="card-title">Configuration Checklist</span></div>
    <ol style="padding-left:20px;font-size:14px;line-height:2">
      <li>Edit <code>.env</code> and set <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code></li>
      <li>Set <code>AI_API_KEY</code> to your OpenAI/Anthropic/OpenRouter key</li>
      <li>Set <code>AI_PROVIDER</code> and <code>AI_MODEL</code> accordingly</li>
      <li>Create the database in phpMyAdmin: <code>chatbot_db</code> (UTF8MB4)</li>
      <li>Import <code>database/schema.sql</code> via phpMyAdmin</li>
      <li>Open <a href="login.php">login.php</a> and register your admin account</li>
      <li>Go to Admin &gt; Settings and configure your chatbot</li>
      <li>Add knowledge in Admin &gt; Knowledge Base</li>
      <li>Delete <code>setup.php</code></li>
    </ol>
  </div>
</div>
</body>
</html>
