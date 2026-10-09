<?php
/**
 * GaaTiTrack Reusable Component Helpers
 */

if (!defined('GAATITRACK_CONFIG_LOADED')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Shipment Status Dictionary
 */
function get_status_dictionary() {
    return [
        0 => 'Item Accepted by Courier',
        1 => 'Collected',
        2 => 'Shipped',
        3 => 'In-Transit',
        4 => 'Arrived At Destination',
        5 => 'Out for Delivery',
        6 => 'Ready to Pickup',
        7 => 'Delivered',
        8 => 'Picked-up',
        9 => 'Unsuccessful Delivery Attempt',
    ];
}

function get_status_label($statusCode) {
    $dict = get_status_dictionary();
    return $dict[$statusCode] ?? 'Unknown Status';
}

/**
 * Render Status Badge
 */
function render_status_badge($statusCode) {
    $code = (int)$statusCode;
    $label = get_status_label($code);
    return sprintf('<span class="gt-badge gt-badge-%d">%s</span>', $code, e($label));
}

function status_label($statusCode) {
    return get_status_label($statusCode);
}

function get_status_badge($statusCode) {
    return render_status_badge($statusCode);
}

/**
 * Render Alert Box
 */
function render_alert($message, $type = 'info') {
    $allowed = ['success', 'error', 'info', 'warning'];
    $t = in_array($type, $allowed, true) ? $type : 'info';
    return sprintf(
        '<div class="gt-alert gt-alert-%s" role="alert">%s</div>',
        $t,
        e($message)
    );
}

/**
 * Render Stat Card (for operational dashboard)
 */
function render_stat_card($label, $value, $iconSvg, $variant = 'default') {
    $classModifier = $variant !== 'default' ? ' stat-' . e($variant) : '';
    return '
    <div class="gt-stat-card' . $classModifier . '">
        <div class="gt-stat-info">
            <div class="gt-stat-label">' . e($label) . '</div>
            <div class="gt-stat-value">' . e($value) . '</div>
        </div>
        <div class="gt-stat-icon">' . $iconSvg . '</div>
    </div>';
}

/**
 * Render Empty State
 */
function render_empty_state($title, $description, $actionHtml = '') {
    return '
    <div class="gt-empty-state">
        <div class="gt-empty-state-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
        </div>
        <div class="gt-empty-state-title">' . e($title) . '</div>
        <div class="gt-empty-state-desc">' . e($description) . '</div>
        ' . $actionHtml . '
    </div>';
}

/**
 * Render Breadcrumbs
 */
function render_breadcrumb($items = []) {
    if (empty($items)) return '';
    $html = '<nav aria-label="breadcrumb" style="margin-bottom: 1.25rem;"><ol style="display:flex;list-style:none;padding:0;margin:0;gap:8px;font-size:0.875rem;color:var(--gt-navy-500);">';
    $total = count($items);
    $i = 0;
    foreach ($items as $label => $url) {
        $i++;
        if ($i === $total || empty($url)) {
            $html .= '<li style="color:var(--gt-navy-800);font-weight:600;">' . e($label) . '</li>';
        } else {
            $html .= '<li><a href="' . e($url) . '">' . e($label) . '</a></li><li aria-hidden="true">/</li>';
        }
    }
    $html .= '</ol></nav>';
    return $html;
}
