<?php
/**
 * 500 Internal Server Error
 */
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error — MobileShop</title>
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
            background: linear-gradient(135deg, #ef4444, #f97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 16px;
        }
        .error-icon {
            font-size: 3rem;
            margin-bottom: 24px;
            color: #ef4444;
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
        <div class="error-icon"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="error-code">500</div>
        <h1>Something Went Wrong</h1>
        <p>We're sorry — our server encountered an unexpected error. Our team has been notified and is working to fix it.</p>
        <a href="/mobile-shop" class="btn">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>
</body>
</html>
