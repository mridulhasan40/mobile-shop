<?php
/**
 * 404 Page Not Found
 */
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/config/database.php';
}
http_response_code(404);
$pageTitle = '404 — Page Not Found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — <?php echo defined('SITE_NAME') ? SITE_NAME : 'MobileShop'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #334155;
        }
        .error-box {
            text-align: center;
            padding: 60px 40px;
            max-width: 500px;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 16px;
        }
        .error-icon {
            font-size: 3rem;
            margin-bottom: 24px;
            color: #94a3b8;
        }
        h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 12px; }
        p  { color: #64748b; margin-bottom: 32px; line-height: 1.6; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            color: #fff;
            padding: 12px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: opacity .2s;
        }
        .btn:hover { opacity: .85; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon"><i class="fas fa-compass"></i></div>
        <div class="error-code">404</div>
        <h1>Page Not Found</h1>
        <p>The page you're looking for doesn't exist or may have been moved. Let's get you back on track.</p>
        <a href="<?php echo defined('SITE_URL') ? SITE_URL : '/mobile-shop'; ?>" class="btn">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>
</body>
</html>
