<?php
// Don't require login for error page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_details = $_SESSION['error_details'] ?? null;
$click_count = $_SESSION['error_clicks'] ?? 0;

// Increment click count if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'reveal_error') {
    $click_count++;
    $_SESSION['error_clicks'] = $click_count;
}

$show_error = $click_count >= 3;

// Clear error on page load (optional)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['error_clicks'] = 0;
    $click_count = 0;
    $show_error = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oops! Something Went Wrong</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --accent: #4f6ef7;
            --red: #ef4444;
            --green: #10b981;
            --text: #1f2937;
            --text2: #6b7280;
            --bg: #f9fafb;
            --card: #ffffff;
            --border: #e5e7eb;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
        }
        
        .error-card {
            background: var(--card);
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-icon {
            font-size: 80px;
            margin-bottom: 24px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        h1 {
            font-size: 32px;
            color: var(--text);
            margin-bottom: 12px;
        }
        
        .error-message {
            font-size: 16px;
            color: var(--text2);
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .button-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        
        .btn-primary:hover {
            background: #4052d4;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 110, 247, 0.3);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--accent);
            border: 2px solid var(--accent);
        }
        
        .btn-secondary:hover {
            background: var(--accent);
            color: white;
        }
        
        .error-details {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid var(--border);
            text-align: left;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }
        
        .error-details.show {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .error-details h3 {
            color: var(--red);
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: var(--text);
            text-align: left;
            overflow-x: auto;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
        }
        
        .reveal-hint {
            font-size: 12px;
            color: var(--text2);
            margin-top: 20px;
        }
        
        .click-counter {
            font-size: 12px;
            color: var(--accent);
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-card">
            <div class="error-icon">⚠️</div>
            
            <h1>Oops! Something Went Wrong</h1>
            <p class="error-message">
                We encountered an unexpected error. Our team has been notified and we're working on fixing it.<br>
                <strong>Sorry for the inconvenience!</strong>
            </p>
            
            <div class="button-group">
                <a href="index.php" class="btn btn-primary">
                    ← Back to Home
                </a>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="reveal_error">
                    <button type="submit" class="btn btn-secondary">
                        🔍 Error Details
                    </button>
                </form>
            </div>
            
            <?php if ($show_error && $error_details): ?>
            <div class="error-details show">
                <h3>Technical Details (Visible to Developers):</h3>
                
                <div class="error-box">
<strong><?= htmlspecialchars($error_details['type'] ?? 'Error') ?></strong>: <?= htmlspecialchars($error_details['message'] ?? 'Unknown error') ?>

<strong>File:</strong> <?= htmlspecialchars($error_details['file'] ?? 'Unknown') ?>
<strong>Line:</strong> <?= htmlspecialchars($error_details['line'] ?? 'Unknown') ?>
<?php if (!empty($error_details['errno'])): ?>
<strong>Error Code:</strong> <?= htmlspecialchars($error_details['errno']) ?>
<?php endif; ?>
                </div>
                
                <?php if (!empty($error_details['trace'])): ?>
                <div style="margin-top: 12px;">
                    <strong style="color: var(--red);">Stack Trace:</strong>
                    <div class="error-box" style="margin-top: 8px; max-height: 300px; overflow-y: auto;">
<?php 
foreach ($error_details['trace'] as $i => $trace) {
    echo "#{$i} ";
    if (!empty($trace['file'])) {
        echo htmlspecialchars($trace['file']) . "(" . htmlspecialchars($trace['line'] ?? '0') . "): ";
    }
    if (!empty($trace['class'])) {
        echo htmlspecialchars($trace['class']) . htmlspecialchars($trace['type'] ?? '->');
    }
    if (!empty($trace['function'])) {
        echo htmlspecialchars($trace['function']) . "()";
    }
    echo "\n";
}
?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="reveal-hint">
                 Click the "Error Details" button multiple times to reveal technical information.
                <?php if (!$show_error && $click_count > 0): ?>
                <div class="click-counter">
                    (<?= 3 - $click_count ?> more clicks to reveal)
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
