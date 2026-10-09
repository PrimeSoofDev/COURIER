<?php
/**
 * GaaTiTrack Public Shipment Tracking API Endpoint
 * Provides structured JSON responses for live map rendering, route polylines,
 * animated markers, and real-time polling updates.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/components.php';
require_once __DIR__ . '/../includes/geocoding.php';

// Rate Limiting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$now = time();
if (!isset($_SESSION['api_track_rate'])) {
    $_SESSION['api_track_rate'] = ['count' => 1, 'start' => $now];
} else {
    if ($now - $_SESSION['api_track_rate']['start'] < 60) {
        $_SESSION['api_track_rate']['count']++;
        if ($_SESSION['api_track_rate']['count'] > 60) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Too many tracking requests. Please wait a moment.'
            ]);
            exit;
        }
    } else {
        $_SESSION['api_track_rate'] = ['count' => 1, 'start' => $now];
    }
}

$ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if (empty($ref)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Tracking reference number is required.'
    ]);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9\-_]{6,30}$/', $ref)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid tracking reference number format.'
    ]);
    exit;
}

try {
    // Query parcel
    $stmt = $pdo->prepare("SELECT id, reference_number, type, from_branch_id, to_branch_id, sender_address, weight, height, width, length, price, status, date_created, recipient_name, recipient_address FROM parcels WHERE reference_number = :ref LIMIT 1");
    $stmt->execute([':ref' => $ref]);
    $parcel = $stmt->fetch();

    if (!$parcel) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => "No shipment found matching tracking number '{$ref}'."
        ]);
        exit;
    }

    // Origin Branch
    $originBranch = null;
    if (!empty($parcel['from_branch_id'])) {
        $bStmt = $pdo->prepare("SELECT id, branch_code, street, city, state, zip_code, country, contact FROM branches WHERE id = :id");
        $bStmt->execute([':id' => $parcel['from_branch_id']]);
        $originBranch = $bStmt->fetch();
    }

    // Destination Branch
    $destBranch = null;
    if (!empty($parcel['to_branch_id'])) {
        $bStmt = $pdo->prepare("SELECT id, branch_code, street, city, state, zip_code, country, contact FROM branches WHERE id = :id");
        $bStmt->execute([':id' => $parcel['to_branch_id']]);
        $destBranch = $bStmt->fetch();
    }

    // Tracking Event History
    $tStmt = $pdo->prepare("SELECT status, date_created FROM parcel_tracks WHERE parcel_id = :pid ORDER BY date_created ASC, id ASC");
    $tStmt->execute([':pid' => $parcel['id']]);
    $history = $tStmt->fetchAll();

    $events = [];
    $originCity = $originBranch ? $originBranch['city'] : 'Origin Hub';
    
    // Initial Booking Event
    $events[] = [
        'status_code' => 0,
        'status_label' => 'Item Accepted by Courier',
        'location' => $originCity . ' Hub',
        'date' => $parcel['date_created'],
        'formatted_date' => date('M d, Y - h:i A', strtotime($parcel['date_created'])),
        'note' => 'Consignment booked & recorded at ' . $originCity . ' Hub',
        'completed' => true
    ];

    foreach ($history as $h) {
        $code = (int)$h['status'];
        $label = get_status_label($code);
        $loc = $originCity . ' Hub';
        if ($code >= 4 && $destBranch) {
            $loc = $destBranch['city'] . ' Hub';
        } elseif ($code >= 2) {
            $loc = 'National Transit Corridor';
        }
        $events[] = [
            'status_code' => $code,
            'status_label' => $label,
            'location' => $loc,
            'date' => $h['date_created'],
            'formatted_date' => date('M d, Y - h:i A', strtotime($h['date_created'])),
            'note' => 'Consignment status updated to ' . $label,
            'completed' => true
        ];
    }

    // Geocoding & Route Coordinates
    $originCoords = resolve_branch_coordinates($originBranch, $parcel['sender_address']);
    $destCoords = resolve_destination_coordinates($destBranch, $parcel['recipient_address']);

    // If destination coordinates not found, fallback to Chicago or Los Angeles corridor safely
    if (!$destCoords) {
        $destCoords = [
            'lat' => 41.8789,
            'lng' => -87.6359,
            'city' => 'Chicago',
            'state' => 'IL',
            'label' => 'Destination Area',
            'address' => $parcel['recipient_address'],
            'verified' => false
        ];
    }

    // Fetch optional API key from system settings (e.g. OpenRouteService)
    $sysApiKey = '';
    try {
        $sysRow = $pdo->query("SELECT api_key FROM system_settings WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $sysApiKey = $sysRow['api_key'] ?? '';
    } catch (Exception $e) {}

    $routeWaypoints = calculate_route_waypoints($originCoords, $destCoords, 60, $sysApiKey);

    // Current Milestone Progress Percentage
    $statusCode = (int)$parcel['status'];
    $progressPercent = get_milestone_route_progress($statusCode);

    // Compute current marker position along route waypoints
    $waypointIndex = (int)round($progressPercent * (count($routeWaypoints) - 1));
    $currentPos = $routeWaypoints[$waypointIndex] ?? $originCoords;
    $currentPos['milestone_label'] = get_status_label($statusCode);
    $currentPos['progress_percent'] = round($progressPercent * 100);

    // Masked Recipient
    $recipientParts = explode(' ', trim($parcel['recipient_name'] ?? 'Recipient'));
    $maskedRecipient = htmlspecialchars($recipientParts[0], ENT_QUOTES, 'UTF-8');
    if (isset($recipientParts[1])) {
        $maskedRecipient .= ' ' . substr($recipientParts[1], 0, 1) . '***';
    }

    // Estimated Delivery Calculation
    $createdTime = strtotime($parcel['date_created']);
    $estDelivery = date('M d, Y', $createdTime + (3 * 86400));
    $latestEventDate = !empty($events) ? end($events)['formatted_date'] : date('M d, Y - h:i A');

    echo json_encode([
        'success' => true,
        'reference_number' => $parcel['reference_number'],
        'status_code' => $statusCode,
        'status_label' => get_status_label($statusCode),
        'delivery_type' => (int)$parcel['type'] === 2 ? 'Branch Counter Pickup' : 'Express Doorstep Delivery',
        'is_delivered' => in_array($statusCode, [7, 8], true),
        'is_out_for_delivery' => ($statusCode === 5),
        'origin' => $originCoords,
        'destination' => $destCoords,
        'current_position' => $currentPos,
        'progress_percent' => round($progressPercent * 100),
        'estimated_delivery' => $estDelivery,
        'latest_update' => $latestEventDate,
        'masked_recipient' => $maskedRecipient,
        'weight' => $parcel['weight'],
        'dimensions' => $parcel['length'] . 'x' . $parcel['width'] . 'x' . $parcel['height'],
        'waypoints' => $routeWaypoints,
        'timeline' => array_reverse($events) // latest first for display
    ], JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    app_log("API Tracking error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'A temporary database error occurred while querying shipment details.'
    ]);
}
