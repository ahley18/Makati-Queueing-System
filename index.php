<?php
// index.php
// PHP version of queue-backend-simple.js for typical web hosting

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Set timezone to Asia/Manila for all date/time operations
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Manila');
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// File for storing queue data
$data_file = __DIR__ . '/queue_data.json';

// Ensure the data file exists and is writable
if (!file_exists($data_file)) {
    $initial_data = [
        'queueTickets' => [],
        'ticketCounters' => [],
        'system_enabled' => true // Initialize system_enabled
    ];
    file_put_contents($data_file, json_encode($initial_data, JSON_PRETTY_PRINT));
}

// Check if file is writable
if (!is_writable($data_file)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database file is not writable',
        'file' => $data_file,
        'permissions' => substr(sprintf('%o', fileperms($data_file)), -4)
    ]);
    exit();
}

// --- In-memory data (will be loaded from file) ---
$services = [
    [ 'id' => 1,  'code' => 'BP', 'name' => 'Business Permit', 'default_counter' => 1 ],
    [ 'id' => 2,  'code' => 'RT', 'name' => 'Real Estate Tax', 'default_counter' => 2 ],
    [ 'id' => 3,  'code' => 'CT', 'name' => 'Community Tax', 'default_counter' => 3 ],
    [ 'id' => 4,  'code' => 'HC', 'name' => 'Health Certificate', 'default_counter' => 4 ],
    [ 'id' => 5,  'code' => 'VP', 'name' => 'VaxCert PH', 'default_counter' => 5 ],
    [ 'id' => 6,  'code' => 'MC', 'name' => 'Makatizen Card (General)', 'default_counter' => 6 ],
    [ 'id' => 7,  'code' => 'MR', 'name' => 'Makatizen Renewal', 'default_counter' => 7 ],
    [ 'id' => 8,  'code' => 'MB', 'name' => 'Makatizen Biometrics', 'default_counter' => 8 ],
    [ 'id' => 9,  'code' => 'MI', 'name' => 'Makatizen Inquiry / Follow-ups', 'default_counter' => 9 ],
    [ 'id' => 10, 'code' => 'CR', 'name' => 'Makatizen Card Releasing', 'default_counter' => 10 ],
    [ 'id' => 11, 'code' => 'GC', 'name' => 'Makatizen GCash Concern', 'default_counter' => 11 ]
];

// --- Helper functions ---
function load_data($file) {
    try {
        if (!file_exists($file)) {
            return [ 'queueTickets' => [], 'ticketCounters' => [], 'system_enabled' => true ];
        }
        
        $json = file_get_contents($file);
        if ($json === false) {
            throw new Exception('Failed to read data file');
        }
        
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in data file: ' . json_last_error_msg());
        }
        
        if (!is_array($data)) {
            $data = [ 'queueTickets' => [], 'ticketCounters' => [], 'system_enabled' => true ];
        }
        
        if (!isset($data['queueTickets'])) {
            $data['queueTickets'] = [];
        }
        
        if (!isset($data['ticketCounters'])) {
            $data['ticketCounters'] = [];
        }

        if (!isset($data['system_enabled'])) {
            $data['system_enabled'] = true;
        }
        
        return $data;
    } catch (Exception $e) {
        error_log('[QUEUE_BACKEND] Error loading data: ' . $e->getMessage());
        return [ 'queueTickets' => [], 'ticketCounters' => [], 'system_enabled' => true ];
    }
}

function save_data($file, $data) {
    try {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new Exception('Failed to encode data to JSON');
        }
        
        $result = file_put_contents($file, $json);
        if ($result === false) {
            throw new Exception('Failed to write data file');
        }
        
        return true;
    } catch (Exception $e) {
        error_log('[QUEUE_BACKEND] Error saving data: ' . $e->getMessage());
        return false;
    }
}

function uuidv4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function today_str() {
    return date('Y-m-d');
}

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function get_service_by_counter($counter, $services) {
    foreach ($services as $s) {
        if ($s['default_counter'] == $counter) {
            return $s;
        }
    }
    return null;
}

function get_service_by_id($id, $services) {
    foreach ($services as $s) {
        if ($s['id'] == $id) {
            return $s;
        }
    }
    return null;
}

function log_error($msg) {
    error_log('[QUEUE_BACKEND] ' . $msg);
}

// --- Load data ---
try {
    $data = load_data($data_file);
    $queueTickets = &$data['queueTickets'];
    $ticketCounters = &$data['ticketCounters'];
    $system_enabled = isset($data['system_enabled']) ? $data['system_enabled'] : true;
    // Migrate counters: if not present, initialize from old $services
    if (!isset($data['counters'])) {
        $data['counters'] = [
            [ "counter_number" => 1, "name" => "Business Permit", "prefix" => "BP", "enabled" => true ],
            [ "counter_number" => 2, "name" => "Real Estate Tax", "prefix" => "RT", "enabled" => true ],
            [ "counter_number" => 3, "name" => "Community Tax", "prefix" => "CT", "enabled" => true ],
            [ "counter_number" => 4, "name" => "Health Certificate", "prefix" => "HC", "enabled" => true ],
            [ "counter_number" => 5, "name" => "VaxCert PH", "prefix" => "VP", "enabled" => true ],
            [ "counter_number" => 6, "name" => "Makatizen Card (General)", "prefix" => "MC", "enabled" => true ],
            [ "counter_number" => 7, "name" => "Makatizen Renewal", "prefix" => "MR", "enabled" => true ],
            [ "counter_number" => 8, "name" => "Makatizen Biometrics", "prefix" => "MB", "enabled" => true ],
            [ "counter_number" => 9, "name" => "Makatizen Inquiry / Follow-ups", "prefix" => "MI", "enabled" => true ],
            [ "counter_number" => 10, "name" => "Makatizen Card Releasing", "prefix" => "CR", "enabled" => true ],
            [ "counter_number" => 11, "name" => "Makatizen GCash Concern", "prefix" => "GC", "enabled" => true ]
        ];
        save_data($data_file, $data);
    }
    $counters = &$data['counters'];
} catch (Exception $e) {
    json_response([
        'error' => 'Failed to load data',
        'message' => $e->getMessage()
    ], 500);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];
// --- System enable/disable endpoints ---
if ($action === 'system_status' && $method === 'GET') {
    json_response(['success' => true, 'system_enabled' => $system_enabled]);
}
if ($action === 'set_system_status' && in_array($method, ['POST','PATCH'])) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['enabled'])) {
        json_response(['success' => false, 'error' => 'Missing enabled parameter'], 400);
    }
    $data['system_enabled'] = !!$input['enabled'];
    if (!save_data($data_file, $data)) {
        json_response(['success' => false, 'error' => 'Failed to update system status'], 500);
    }
    json_response(['success' => true, 'system_enabled' => $data['system_enabled']]);
}
// --- Block actions if system is disabled (except admin/analytics/status endpoints) ---
$actions_blocked_if_disabled = [
    'enqueue', 'status_update', 'admin_clear', 'clear', 'delete_tickets', 'reset_system', 'midnight_reset'
];
if (in_array($action, $actions_blocked_if_disabled) && !$system_enabled) {
    json_response([
        'success' => false,
        'error' => 'The queue system is currently DISABLED by the administrator. Please try again later.'
    ], 403);
}

// Add basic request logging
log_error("Request: $method $action");

// --- Endpoints ---
if ($action === 'health' && $method === 'GET') {
    json_response([
        'status' => 'healthy',
        'timestamp' => date('c'),
        'uptime' => 0,
        'tickets_count' => count($queueTickets),
        'data_file' => $data_file,
        'file_exists' => file_exists($data_file),
        'file_writable' => is_writable($data_file)
    ]);
}
elseif ($action === 'status' && $method === 'GET') {
    // Get All Current Queue Statuses for all counters in $counters
    $result = [];
    foreach ($counters as $c) {
        $activeTicket = null;
        foreach ($queueTickets as $t) {
            if ($t['counter'] == $c['counter_number'] && in_array($t['status'], ['Waiting', 'Now Serving'])) {
                $activeTicket = $t;
                break;
            }
        }
        $result[] = [
            'counter' => $c['counter_number'],
            'code' => $activeTicket ? $activeTicket['ticket_code'] : null,
            'service' => $c['name'],
            'status' => $activeTicket ? $activeTicket['status'] : 'Closed'
        ];
    }
    json_response($result);
}
elseif ($action === 'enqueue' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $service_type = $input['service_type'] ?? null;
    $counter = $input['counter'] ?? null;
    
    if (!$service_type || !$counter) {
        log_error('Missing service_type or counter in enqueue');
        json_response([ 'success' => false, 'error' => 'service_type and counter are required' ], 400);
    }
    
    // Find the counter in $counters (persistent, editable)
    $counterObj = null;
    foreach ($counters as $c) {
        if ($c['counter_number'] == $counter) {
            $counterObj = $c;
            break;
        }
    }
    if (!$counterObj) {
        log_error('Counter not found: ' . $counter);
        json_response([ 'success' => false, 'error' => 'Counter not found' ], 404);
    }
    
    if (strcasecmp($counterObj['name'], $service_type) !== 0) {
        log_error('Service type mismatch: got ' . $service_type . ', expected ' . $counterObj['name']);
        json_response([ 'success' => false, 'error' => 'Service type does not match counter assignment' ], 400);
    }
    
    // Generate ticket number
    $today = today_str();
    $key = $counterObj['prefix'] . '-' . $today;
    if (!isset($ticketCounters[$key])) {
        $ticketCounters[$key] = 0;
    }
    $ticketCounters[$key]++;
    $ticketNumber = $counterObj['prefix'] . '-' . str_pad($ticketCounters[$key], 3, '0', STR_PAD_LEFT);
    
    // Create ticket
    $ticket = [
        'id' => uuidv4(),
        'ticket_code' => $ticketNumber,
        'service_id' => $counter, // Use counter number as service_id for new counters
        'counter' => $counter,
        'status' => 'Waiting',
        'created_at' => date('c'),
        'updated_at' => date('c')
    ];
    
    $queueTickets[] = $ticket;
    
    if (!save_data($data_file, $data)) {
        json_response([ 'success' => false, 'error' => 'Failed to save ticket' ], 500);
    }
    
    json_response([
        'success' => true,
        'ticket_code' => $ticketNumber,
        'message' => "Ticket added to queue at Counter $counter"
    ]);
}
elseif ($action === 'summary' && $method === 'GET') {
    $today = today_str();
    $todayTickets = array_filter($queueTickets, function($t) use ($today) {
        return strpos($t['created_at'], $today) === 0;
    });
    
    $totalActive = count(array_filter($todayTickets, function($t) {
        return in_array($t['status'], ['Waiting', 'Now Serving']);
    }));
    
    $nowServing = count(array_filter($todayTickets, function($t) {
        return $t['status'] === 'Now Serving';
    }));
    
    $waiting = count(array_filter($todayTickets, function($t) {
        return $t['status'] === 'Waiting';
    }));
    
    $counters = array_map(function($t) {
        return [
            'counter' => $t['counter'],
            'code' => $t['ticket_code'],
            'status' => $t['status']
        ];
    }, array_filter($todayTickets, function($t) {
        return in_array($t['status'], ['Waiting', 'Now Serving']);
    }));
    
    json_response([
        'total_active' => $totalActive,
        'now_serving' => $nowServing,
        'waiting' => $waiting,
        'counters' => $counters
    ]);
}
elseif ($action === 'admin_tickets' && $method === 'GET') {
    // Return ALL tickets, not just today's, so frontend can categorize by date
    $ticketsWithWaitTime = array_map(function($ticket) use ($services, $counters) {
        $service = get_service_by_id($ticket['service_id'], $services);
        $waitTime = 0;
        // Calculate wait time for completed tickets
        if ($ticket['status'] === 'Done' && isset($ticket['updated_at'])) {
            $waitTime = floor((strtotime($ticket['updated_at']) - strtotime($ticket['created_at'])) / 60);
        } else if (in_array($ticket['status'], ['Waiting', 'Now Serving'])) {
            // For active tickets, calculate wait time from creation to now
            $waitTime = floor((strtotime(date('c')) - strtotime($ticket['created_at'])) / 60);
        }
        // If service is unknown, try to get from counters
        $serviceName = $service ? $service['name'] : 'Unknown';
        if ($serviceName === 'Unknown') {
            foreach ($counters as $c) {
                if ($c['counter_number'] == $ticket['counter']) {
                    $serviceName = $c['name'];
                    break;
                }
            }
        }
        return array_merge($ticket, [
            'wait_time_minutes' => $waitTime,
            'service_name' => $serviceName
        ]);
    }, $queueTickets);
    json_response([
        'success' => true,
        'tickets' => array_values($ticketsWithWaitTime),
        'total' => count($queueTickets),
        'active' => count(array_filter($queueTickets, function($t) {
            return in_array($t['status'], ['Waiting', 'Now Serving']);
        })),
        'completed' => count(array_filter($queueTickets, function($t) {
            return $t['status'] === 'Done';
        }))
    ]);
}
// --- Counter management endpoints ---
if ($action === 'admin_counters' && $method === 'GET') {
    $result = [];
    foreach ($counters as $c) {
        $queueLength = count(array_filter($queueTickets, function($t) use ($c) {
            return $t['counter'] == $c['counter_number'] && $t['status'] === 'Waiting';
        }));
        $activeTicket = null;
        foreach ($queueTickets as $t) {
            if ($t['counter'] == $c['counter_number'] && in_array($t['status'], ['Waiting', 'Now Serving'])) {
                $activeTicket = $t;
                break;
            }
        }
        $result[] = [
            'counter_number' => $c['counter_number'],
            'name' => $c['name'],
            'prefix' => $c['prefix'],
            'enabled' => $c['enabled'],
            'icon' => $c['icon'] ?? '❓',
            'ticket_number' => $activeTicket ? $activeTicket['ticket_code'] : null,
            'queue_length' => $queueLength,
            'status' => $activeTicket ? 'open' : 'closed',
            'service_name' => $c['name']
        ];
    }
    json_response([ 'success' => true, 'counters' => $result ]);
}
if ($action === 'edit_counter' && in_array($method, ['PATCH','POST'])) {
    $input = json_decode(file_get_contents('php://input'), true);
    $num = intval($input['counter_number'] ?? 0);
    foreach ($counters as &$c) {
        if ($c['counter_number'] == $num) {
            if (isset($input['name'])) $c['name'] = $input['name'];
            if (isset($input['prefix'])) $c['prefix'] = $input['prefix'];
            if (isset($input['enabled'])) $c['enabled'] = !!$input['enabled'];
            if (isset($input['icon'])) $c['icon'] = $input['icon'];
            if (isset($input['counter_number_new'])) $c['counter_number'] = intval($input['counter_number_new']);
            break;
        }
    }
    if (!save_data($data_file, $data)) {
        json_response([ 'success' => false, 'error' => 'Failed to save counter changes' ], 500);
    }
    json_response([ 'success' => true, 'counters' => $counters ]);
}
if ($action === 'delete_counter' && $method === 'DELETE') {
    $num = intval($_GET['counter_number'] ?? 0);
    $found = false;
    foreach ($counters as $i => $c) {
        if ($c['counter_number'] == $num) {
            array_splice($counters, $i, 1);
            $found = true;
            break;
        }
    }
    if (!$found) {
        json_response([ 'success' => false, 'error' => 'Counter not found' ], 404);
    }
    if (!save_data($data_file, $data)) {
        json_response([ 'success' => false, 'error' => 'Failed to delete counter' ], 500);
    }
    json_response([ 'success' => true, 'counters' => $counters ]);
}
elseif ($action === 'add_counter' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $num = intval($input['counter_number'] ?? 0);
    $name = trim($input['name'] ?? '');
    $prefix = strtoupper(trim($input['prefix'] ?? ''));
    $enabled = isset($input['enabled']) ? !!$input['enabled'] : true;
    $icon = $input['icon'] ?? '❓';
    if (!$num || !$name || !$prefix) {
        json_response([ 'success' => false, 'error' => 'All fields are required' ], 400);
    }
    foreach ($counters as $c) {
        if ($c['counter_number'] == $num) {
            json_response([ 'success' => false, 'error' => 'Counter number already exists' ], 400);
        }
        if ($c['prefix'] === $prefix) {
            json_response([ 'success' => false, 'error' => 'Prefix already exists' ], 400);
        }
    }
    $counters[] = [
        'counter_number' => $num,
        'name' => $name,
        'prefix' => $prefix,
        'enabled' => $enabled,
        'icon' => $icon
    ];
    $data['counters'] = $counters;
    save_data($data_file, $data);
    json_response([ 'success' => true, 'counters' => $counters ]);
}
elseif ($action === 'admin_clear' && in_array($method, ['DELETE', 'POST'])) {
    $ticket_code = $_GET['ticket_code'] ?? null;
    if (!$ticket_code) {
        json_response([ 'success' => false, 'error' => 'ticket_code is required' ], 400);
    }
    
    // Optimized search: find ticket index first
    $ticketIndex = null;
    for ($i = 0; $i < count($queueTickets); $i++) {
        if ($queueTickets[$i]['ticket_code'] === $ticket_code) {
            $ticketIndex = $i;
            break; // Early exit once found
        }
    }
    
    if ($ticketIndex === null) {
        json_response([ 'success' => false, 'error' => 'Ticket not found' ], 404);
    }
    
    // Update the specific ticket
    $queueTickets[$ticketIndex]['status'] = 'Done';
    $queueTickets[$ticketIndex]['updated_at'] = date('c');
    $queueTickets[$ticketIndex]['manually_resolved'] = true;
    
    if (!save_data($data_file, $data)) {
        json_response([ 'success' => false, 'error' => 'Failed to save changes' ], 500);
    }
    
    json_response([ 
        'success' => true, 
        'message' => "Ticket $ticket_code has been manually resolved",
        'ticket_code' => $ticket_code,
        'updated_at' => $queueTickets[$ticketIndex]['updated_at']
    ]);
}
elseif ($action === 'admin_analytics' && $method === 'GET') {
    $today = today_str();
    $todayTickets = array_filter($queueTickets, function($t) use ($today) {
        return strpos($t['created_at'], $today) === 0;
    });
    
    $totalTickets = count($todayTickets);
    $activeTickets = count(array_filter($todayTickets, function($t) {
        return in_array($t['status'], ['Waiting', 'Now Serving']);
    }));
    $completedTickets = count(array_filter($todayTickets, function($t) {
        return $t['status'] === 'Done';
    }));
    $autoResolvedTickets = count(array_filter($todayTickets, function($t) {
        return !empty($t['auto_resolved']);
    }));
    $manuallyResolvedTickets = count(array_filter($todayTickets, function($t) {
        return !empty($t['manually_resolved']);
    }));
    $nowServing = count(array_filter($todayTickets, function($t) {
        return $t['status'] === 'Now Serving';
    }));
    $waiting = count(array_filter($todayTickets, function($t) {
        return $t['status'] === 'Waiting';
    }));
    $completedWithWaitTime = array_filter($todayTickets, function($t) {
        return $t['status'] === 'Done';
    });
    $avgWaitTime = count($completedWithWaitTime) > 0
        ? floor(array_reduce($completedWithWaitTime, function($sum, $t) {
            return $sum + (strtotime($t['updated_at']) - strtotime($t['created_at'])) / 60;
        }, 0) / count($completedWithWaitTime))
        : 0;
    $serviceRate = $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100) : 0;
    // Hourly ticket distribution (0-23)
    $hourlyDistribution = array_fill(0, 24, 0);
    foreach ($todayTickets as $t) {
        $hour = (int)date('G', strtotime($t['created_at'])); // 0-23
        $hourlyDistribution[$hour]++;
    }
    // Service performance by counter
    $counterServed = [];
    foreach ($todayTickets as $t) {
        if ($t['status'] === 'Done') {
            $counter = $t['counter'];
            if (!isset($counterServed[$counter])) {
                $service = get_service_by_counter($counter, $services);
                $counterServed[$counter] = [
                    'counter_number' => $counter,
                    'service_name' => $service ? $service['name'] : 'Unknown',
                    'tickets_served' => 0
                ];
            }
            $counterServed[$counter]['tickets_served']++;
        }
    }
    $servicePerformance = array_values(array_filter($counterServed, function($c) {
        return $c['tickets_served'] > 0;
    }));
    // Daily ticket trends (last 7 days)
    $daily_trends_labels = [];
    $daily_trends_created = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = $date; // Use full date for x-axis
        $count = 0;
        foreach ($queueTickets as $t) {
            if (strpos($t['created_at'], $date) === 0) {
                $count++;
            }
        }
        $daily_trends_labels[] = $label;
        $daily_trends_created[] = $count;
    }
    json_response([
        'success' => true,
        'analytics' => [
            'total_tickets' => $totalTickets,
            'active_tickets' => $activeTickets,
            'completed_tickets' => $completedTickets,
            'auto_resolved' => $autoResolvedTickets,
            'manually_resolved' => $manuallyResolvedTickets,
            'average_wait_time' => $avgWaitTime,
            'service_rate' => $serviceRate,
            'now_serving' => $nowServing,
            'waiting' => $waiting,
            'hourly_ticket_distribution' => $hourlyDistribution,
            'service_performance_by_counter' => $servicePerformance,
            'daily_trends_labels' => $daily_trends_labels,
            'daily_trends_created' => $daily_trends_created
        ]
    ]);
}
elseif ($action === 'status_update' && in_array($method, ['PATCH', 'POST'])) {
    $counter = $_GET['counter'] ?? null;
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? null;
    $status = $input['status'] ?? null;
    if (!$counter || !$code || !$status) {
        json_response([ 'success' => false, 'error' => 'counter, code and status are required' ], 400);
    }
    // Find the counter in $counters (persistent, editable)
    $counterObj = null;
    foreach ($counters as $c) {
        if ($c['counter_number'] == $counter) {
            $counterObj = $c;
            break;
        }
    }
    if (!$counterObj) {
        json_response([ 'success' => false, 'error' => 'Counter not found' ], 404);
    }
    // Optimized search: find ticket index first
    $ticketIndex = null;
    for ($i = 0; $i < count($queueTickets); $i++) {
        if ($queueTickets[$i]['ticket_code'] === $code && $queueTickets[$i]['counter'] == $counter) {
            $ticketIndex = $i;
            break; // Early exit once found
        }
    }
    if ($ticketIndex === null) {
        json_response([ 'success' => false, 'error' => 'Ticket not found' ], 404);
    }
    // Update the specific ticket
    $queueTickets[$ticketIndex]['status'] = $status;
    $queueTickets[$ticketIndex]['updated_at'] = date('c');
    if (!save_data($data_file, $data)) {
        json_response([ 'success' => false, 'error' => 'Failed to save changes' ], 500);
    }
    json_response([ 
        'success' => true, 
        'message' => "Queue updated for Counter $counter",
        'ticket_code' => $code,
        'status' => $status,
        'updated_at' => $queueTickets[$ticketIndex]['updated_at']
    ]);
}
elseif ($action === 'clear' && in_array($method, ['DELETE', 'POST'])) {
    $ticket_code = $_GET['ticket_code'] ?? null;
    if (!$ticket_code) {
        json_response([ 'success' => false, 'error' => 'ticket_code is required' ], 400);
    }
    
    // Optimized search: find ticket index first
    $ticketIndex = null;
    for ($i = 0; $i < count($queueTickets); $i++) {
        if ($queueTickets[$i]['ticket_code'] === $ticket_code) {
            $ticketIndex = $i;
            break; // Early exit once found
        }
    }
    
    if ($ticketIndex === null) {
        json_response([ 'success' => false, 'error' => 'Ticket not found' ], 404);
    }
    
    // Update the specific ticket
    $queueTickets[$ticketIndex]['status'] = 'Done';
    $queueTickets[$ticketIndex]['updated_at'] = date('c');
    
    if (!save_data($data_file, $data)) {
        json_response([ 'success' => false, 'error' => 'Failed to save changes' ], 500);
    }
    
    json_response([ 
        'success' => true, 
        'message' => "Ticket $ticket_code has been completed",
        'ticket_code' => $ticket_code,
        'updated_at' => $queueTickets[$ticketIndex]['updated_at']
    ]);
}
elseif ($action === 'reset_system' && $method === 'POST') {
    // Clear all tickets and reset counters
    $queueTickets = [];
    $ticketCounters = [];
    
    if (!save_data($data_file, $data)) {
        json_response([ 'success' => false, 'error' => 'Failed to reset system' ], 500);
    }
    
    json_response([ 'success' => true, 'message' => 'System reset: All tickets cleared' ]);
}
elseif ($action === 'midnight_reset' && $method === 'POST') {
    // Reset ticket counters for new day (keep existing tickets but reset counters)
    $ticketCounters = [];
    
    if (!save_data($data_file, $data)) {
        json_response([ 'success' => false, 'error' => 'Failed to reset ticket counters' ], 500);
    }
    
    json_response([ 
        'success' => true, 
        'message' => 'Ticket counters reset for new day',
        'current_date' => today_str(),
        'reset_time' => date('c')
    ]);
}
elseif ($action === 'delete_tickets' && $method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $ticketCodes = $input['ticket_codes'] ?? [];
    $date = $input['date'] ?? 'all';
    
    if (empty($ticketCodes)) {
        json_response([ 'success' => false, 'error' => 'No ticket codes provided' ], 400);
    }
    
    $deletedCount = 0;
    $originalCount = count($queueTickets);
    
    // Remove tickets that match the provided ticket codes
    $queueTickets = array_filter($queueTickets, function($ticket) use ($ticketCodes, &$deletedCount) {
        if (in_array($ticket['ticket_code'], $ticketCodes)) {
            $deletedCount++;
            return false; // Remove this ticket
        }
        return true; // Keep this ticket
    });
    
    // Re-index the array
    $queueTickets = array_values($queueTickets);
    
    if (!save_data($data_file, $data)) {
        json_response([ 'success' => false, 'error' => 'Failed to save changes' ], 500);
    }
    
    json_response([
        'success' => true,
        'message' => "Successfully deleted $deletedCount tickets",
        'deleted_count' => $deletedCount,
        'remaining_count' => count($queueTickets),
        'date' => $date
    ]);
}
elseif ($action === 'public_counters' && $method === 'GET') {
    $public = array_map(function($c) {
        return [
            'counter_number' => $c['counter_number'],
            'name' => $c['name'],
            'prefix' => $c['prefix'],
            'enabled' => $c['enabled'],
            'icon' => $c['icon'] ?? '❓'
        ];
    }, $counters);
    json_response(['success' => true, 'counters' => $public]);
}
else {
    // Default response for unknown endpoints
    json_response([
        'error' => 'Not found',
        'action' => $action,
        'method' => $method,
        'available_actions' => [
            'health', 'status', 'summary', 'enqueue', 'admin_tickets',
            'admin_counters', 'admin_clear', 'admin_analytics',
            'status_update', 'clear', 'reset_system'
        ]
    ], 404);
} 