<?php
$lines = file('C:/xampp/htdocs/mobile-shop/assets/css/style.css');

// Find the hero section start line (0-indexed)
$heroStart = null;
$catEnd = null;
foreach ($lines as $i => $line) {
    if (strpos($line, 'Hero Section') !== false && strpos($lines[$i-1] ?? '', '====') !== false) {
        $heroStart = $i - 1; // include the comment line above
    }
    if ($heroStart !== null && strpos($line, 'Product Detail Page') !== false && strpos($lines[$i-1] ?? '', '====') !== false) {
        $catEnd = $i - 1;
        break;
    }
}

echo "Hero starts at line: " . ($heroStart + 1) . "\n";
echo "Product Detail starts at line: " . ($catEnd + 1) . "\n";

$before = array_slice($lines, 0, $heroStart);
$after = array_slice($lines, $catEnd);

$newCSS = <<<'CSS'

/* ==================================================
   Hero Section
   ================================================== */
.hero {
    position: relative;
    padding: 100px 0 80px;
    text-align: center;
    overflow: hidden;
    background: linear-gradient(160deg, #f8faff 0%, #edf0ff 50%, #f0f7ff 100%);
}

.hero::before {
    content: '';
    position: absolute;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(124, 58, 237, 0.06), transparent 70%);
    top: -200px;
    right: -150px;
    pointer-events: none;
}

.hero::after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(0, 153, 204, 0.05), transparent 70%);
    bottom: -100px;
    left: -100px;
    pointer-events: none;
}

.hero .container {
    position: relative;
    z-index: 2;
}

.hero-label {
    display: inline-block;
    font-size: var(--font-size-sm);
    font-weight: 700;
    color: var(--accent-purple);
    text-transform: uppercase;
    letter-spacing: 0.2em;
    margin-bottom: var(--space-5);
}

.hero-title {
    font-size: clamp(2.5rem, 5vw, 4.5rem);
    font-weight: 900;
    line-height: 1.08;
    color: var(--text-primary);
    letter-spacing: -0.03em;
    margin-bottom: var(--space-6);
    max-width: 750px;
    margin-left: auto;
    margin-right: auto;
}

.hero-title span {
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-desc {
    font-size: var(--font-size-lg);
    color: var(--text-secondary);
    max-width: 560px;
    margin: 0 auto var(--space-10);
    line-height: 1.7;
}

.hero-actions {
    display: flex;
    justify-content: center;
    gap: var(--space-4);
    flex-wrap: wrap;
    margin-bottom: var(--space-16);
}

.btn-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-6);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    font-weight: 600;
    font-size: var(--font-size-sm);
    color: var(--text-primary);
    background: transparent;
    cursor: pointer;
    transition: all var(--transition-base);
    text-decoration: none;
    white-space: nowrap;
}

.btn-ghost:hover {
    border-color: var(--text-primary);
    background: var(--bg-secondary);
    transform: translateY(-1px);
}

.btn-ghost.btn-lg {
    padding: var(--space-4) var(--space-8);
    font-size: var(--font-size-base);
}

/* Hero Metrics */
.hero-metrics {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: var(--space-8);
}

.hero-metric {
    text-align: center;
}

.hero-metric strong {
    display: block;
    font-size: var(--font-size-2xl);
    font-weight: 800;
    color: var(--text-primary);
    letter-spacing: -0.02em;
}

.hero-metric span {
    font-size: var(--font-size-xs);
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 500;
}

.hero-metric-divider {
    width: 1px;
    height: 36px;
    background: var(--border-color);
}

/* ==================================================
   Features Strip
   ================================================== */
.features-strip {
    padding: var(--space-6) 0;
    background: #ffffff;
    border-bottom: 1px solid var(--border-color);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-6);
}

.feature-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.feature-icon {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-md);
    background: rgba(124, 58, 237, 0.07);
    color: var(--accent-purple);
    font-size: var(--font-size-base);
    flex-shrink: 0;
}

.feature-item strong {
    display: block;
    font-size: var(--font-size-sm);
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.3;
}

.feature-item span {
    font-size: var(--font-size-xs);
    color: var(--text-muted);
}

/* ==================================================
   Sections
   ================================================== */
.section {
    padding: var(--space-16) 0;
}

.section--gray {
    background: var(--bg-secondary);
}

.section-top {
    text-align: center;
    margin-bottom: var(--space-10);
}

.section-top h2 {
    font-size: var(--font-size-3xl);
    font-weight: 800;
    color: var(--text-primary);
    letter-spacing: -0.02em;
    margin-bottom: var(--space-2);
}

.section-top p {
    color: var(--text-muted);
    font-size: var(--font-size-base);
}

.section-top--row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    text-align: left;
}

/* ==================================================
   Category Section
   ================================================== */
.category-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: var(--space-4);
}

.category-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-8) var(--space-4);
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    text-decoration: none;
    transition: all var(--transition-base);
    text-align: center;
}

.category-card:hover {
    border-color: var(--accent-purple);
    box-shadow: 0 8px 30px rgba(124, 58, 237, 0.08);
    transform: translateY(-4px);
}

.category-icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(0, 153, 204, 0.07), rgba(124, 58, 237, 0.07));
    border-radius: var(--radius-md);
}

.category-icon i,
.category-card > i {
    font-size: var(--font-size-2xl);
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.category-card h4 {
    font-size: var(--font-size-sm);
    font-weight: 700;
    color: var(--text-primary);
}

.category-card span {
    font-size: var(--font-size-xs);
    color: var(--text-muted);
    font-weight: 500;
}

.category-card:hover span,
.category-card:hover h4 {
    color: var(--accent-purple);
}

CSS;

$output = implode('', $before) . $newCSS . "\n" . implode('', $after);
file_put_contents('C:/xampp/htdocs/mobile-shop/assets/css/style.css', $output);

$newLines = count(file('C:/xampp/htdocs/mobile-shop/assets/css/style.css'));
echo "Done! New file: $newLines lines, " . strlen($output) . " bytes\n";
