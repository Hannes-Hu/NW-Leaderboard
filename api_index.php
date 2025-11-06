<?php
// api.php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow all origins for development. Restrict in production.
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS requests (preflight for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start a session to manage user state (login)
session_start();

function getSetting($conn, $setting_name) {
    $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_name = ?");
    $stmt->bind_param("s", $setting_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return null;
}

function setSetting($conn, $setting_name, $setting_value) {
    $stmt = $conn->prepare("INSERT INTO app_settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $setting_name, $setting_value, $setting_value);
    return $stmt->execute();
}

function generateRandomUserId() {
    return uniqid('user_', true);
}


// Check if user is logged in and their role
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$logged_in_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$logged_in_user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'guest';

// Handle incoming requests
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        $admin_username = getSetting($conn, 'admin_username');
        $admin_password = getSetting($conn, 'admin_password');

        $user_id_found = null;
        $user_role_found = null;
        $user_name_display = null;

        if ($username === $admin_username && $password === $admin_password) {
            $_SESSION['user_id'] = getSetting($conn, 'admin_user_id') ?: generateRandomUserId();
            $_SESSION['user_role'] = 'admin';
            $user_id_found = $_SESSION['user_id'];
            $user_role_found = $_SESSION['user_role'];
            $user_name_display = 'Admin';
        } else {
            // Check for captain logins by selecting password directly from captains table
            $captains_stmt = $conn->prepare("SELECT id, name, password FROM captains WHERE name = ?");
            $captains_stmt->bind_param("s", $username);
            $captains_stmt->execute();
            $captains_result = $captains_stmt->get_result();
            if ($captain_row = $captains_result->fetch_assoc()) {
                // Verify the password
                if ($password === $captain_row['password']) { // In a real app, use password_verify with hashed passwords
                    $_SESSION['user_id'] = $captain_row['id'];
                    $_SESSION['user_role'] = 'captain';
                    $user_id_found = $_SESSION['user_id'];
                    $user_role_found = $_SESSION['user_role'];
                    $user_name_display = $captain_row['name'];
                }
            }
        }

        if ($user_id_found) {
            echo json_encode(['success' => true, 'message' => 'Logged in successfully.', 'userId' => $user_id_found, 'userRole' => $user_role_found, 'userName' => $user_name_display]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        }
        break;

    case 'logout':
        session_unset();
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
        break;

    case 'check_auth':
        echo json_encode(['success' => true, 'userId' => $logged_in_user_id, 'userRole' => $logged_in_user_role]);
        break;

    case 'get_settings':
        $settings = [];
        $settings['numCaptains'] = (int)getSetting($conn, 'num_captains');
        $settings['rosterSize'] = (int)getSetting($conn, 'roster_size');
        $settings['adminUserId'] = getSetting($conn, 'admin_user_id');
        $settings['draftInitialized'] = (bool)getSetting($conn, 'draft_initialized');
        echo json_encode(['success' => true, 'settings' => $settings]);
        break;

    case 'save_settings':
        if (!$is_admin) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $num_captains = $input['numCaptains'] ?? 0;
        $roster_size = $input['rosterSize'] ?? 0;

        if ($num_captains > 0 && $roster_size > 0) {
            setSetting($conn, 'num_captains', $num_captains);
            setSetting($conn, 'roster_size', $roster_size);
            if (!getSetting($conn, 'admin_user_id')) { // Set admin ID only if not already set
                setSetting($conn, 'admin_user_id', $logged_in_user_id);
            }
            setSetting($conn, 'draft_initialized', '0'); // Reset draft status
            echo json_encode(['success' => true, 'message' => 'Settings saved successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid settings provided.']);
        }
        break;

    case 'add_player':
        if (!$is_admin) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        $team = $input['team'] ?? '';
        $nationality = $input['nationality'] ?? '';
        $rating = $input['rating'] ?? 0;
        $start_price = $input['startPrice'] ?? 0;

        if ($name && $rating > 0 && $start_price > 0) {
            // Player status defaults to 'available', current_price to start_price
            $stmt = $conn->prepare("INSERT INTO players (name, team, nationality, rating, start_price, current_price, status) VALUES (?, ?, ?, ?, ?, ?, 'available')");
            $stmt->bind_param("sssiid", $name, $team, $nationality, $rating, $start_price, $start_price);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Player added successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add player.', 'error' => $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing player details or invalid values.']);
        }
        break;
        
    case 'get_settings':
        $settings = [];
        $settings['numCaptains'] = (int)getSetting($conn, 'num_captains');
        $settings['roster_size'] = (int)getSetting($conn, 'roster_size');
        $settings['adminUserId'] = getSetting($conn, 'admin_user_id');
        $settings['draftInitialized'] = (bool)getSetting($conn, 'draft_initialized');
        // Fetch the initial_captain_budget if it exists
        $settings['initial_captain_budget'] = (float)getSetting($conn, 'initial_captain_budget');
        echo json_encode(['success' => true, 'settings' => $settings]);
        break;

    case 'delete_player':
        if (!$is_admin) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $player_id = $input['id'] ?? 0;

        $stmt = $conn->prepare("DELETE FROM players WHERE id = ?");
        $stmt->bind_param("i", $player_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Player deleted successfully!']);
            // When a player is deleted, also remove them from any captain's roster
            $captains_query = $conn->query("SELECT id, team_roster FROM captains");
            while ($captain_row = $captains_query->fetch_assoc()) {
                $roster = json_decode($captain_row['team_roster'], true);
                $updated_roster = array_filter($roster, function($p_id) use ($player_id) {
                    return $p_id != $player_id;
                });
                if (count($roster) !== count($updated_roster)) { // Roster changed
                    $update_captain_stmt = $conn->prepare("UPDATE captains SET team_roster = ? WHERE id = ?");
                    $updated_roster_json = json_encode(array_values($updated_roster)); // Re-index array
                    $update_captain_stmt->bind_param("ss", $updated_roster_json, $captain_row['id']);
                    $update_captain_stmt->execute();
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete player.', 'error' => $conn->error]);
        }
        break;

    case 'update_player_price':
        if (!$is_admin) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $player_id = $input['player_id'] ?? 0;
        $new_price = $input['new_price'] ?? 0;
        
        if ($player_id <= 0 || $new_price <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid player ID or price']);
            break;
        }
        
        $stmt = $conn->prepare("UPDATE players SET current_price = ? WHERE id = ?");
        $stmt->bind_param("di", $new_price, $player_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Player price updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update price']);
        }
        break;

    case 'initialize_draft':
        if (!$is_admin) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        $num_captains = (int)getSetting($conn, 'num_captains');
        $initial_budget = 1000.00; // Example initial budget

        // Clear existing captains
        $conn->query("DELETE FROM captains");

        // Create new captains
        for ($i = 1; $i <= $num_captains; $i++) {
            $captain_id = "captain" . $i; // This is the ID for the captains table (and userId for session)
            $captain_name = "Captain " . $i; // This is the 'name' in the captains table (used for login username)
            $initial_password = "password" . $i; // Default password for this captain
            $empty_roster = json_encode([]);
            // Insert into captains table with the new password column
            $stmt = $conn->prepare("INSERT INTO captains (id, name, budget, password, team_roster) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdss", $captain_id, $captain_name, $initial_budget, $initial_password, $empty_roster);
            $stmt->execute();
        }

        // Reset all players to 'available' status and their starting price
        $stmt = $conn->prepare("UPDATE players SET status = 'available', current_price = start_price, current_bidder_id = NULL, drafted_by = NULL");
        $stmt->execute();

        setSetting($conn, 'draft_initialized', '1');
        echo json_encode(['success' => true, 'message' => 'Draft initialized: Captains and players reset!']);
        break;

    case 'reset_draft':
        if (!$is_admin) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            break;
        }
        $initial_budget = 1000.00; // Example initial budget

        // Reset all players to 'available' status and initial price
        $stmt_players = $conn->prepare("UPDATE players SET status = 'available', current_price = start_price, current_bidder_id = NULL, drafted_by = NULL");
        $stmt_players->execute();

        // Reset captains' budgets and rosters
        $stmt_captains = $conn->prepare("UPDATE captains SET budget = ?, team_roster = ?");
        $empty_roster = json_encode([]);
        $stmt_captains->bind_param("ds", $initial_budget, $empty_roster);
        $stmt_captains->execute();

        echo json_encode(['success' => true, 'message' => 'Draft reset: All players available, captains reset!']);
        break;

    case 'get_players':
        // Players ordered by status (available first), then by name
        $stmt = $conn->prepare("SELECT id, name, team, nationality, rating, start_price, current_price, current_bidder_id, status, drafted_by FROM players ORDER BY FIELD(status, 'available', 'drafted', 'on_bid'), name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $players = [];
        while ($row = $result->fetch_assoc()) {
            $players[] = $row;
        }
        echo json_encode(['success' => true, 'players' => $players]);
        break;

    case 'get_captains':
        $stmt = $conn->prepare("SELECT id, name, budget, team_roster FROM captains"); // Password not retrieved for security
        $stmt->execute();
        $result = $stmt->get_result();
        $captains = [];
        while ($row = $result->fetch_assoc()) {
            $row['team_roster'] = json_decode($row['team_roster']); // Decode JSON string to array
            $captains[] = $row;
        }
        echo json_encode(['success' => true, 'captains' => $captains]);
        break;

    case 'admin_assign_player': // Renamed from finalize_player_draft
        if (!$is_admin) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Must be an admin.']);
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $player_id = $input['player_id'] ?? 0;
        $captain_id = $input['captain_id'] ?? ''; // Admin selects captain
        $buying_price = isset($input['buying_price']) ? floatval($input['buying_price']) : 0; // New: Get buying price

        if (empty($captain_id)) {
            echo json_encode(['success' => false, 'message' => 'No captain selected for assignment.']);
            break;
        }
        if ($buying_price <= 0) { // Validate buying price
            echo json_encode(['success' => false, 'message' => 'Invalid buying price. Must be a positive number.']);
            break;
        }

        $conn->begin_transaction();
        try {
            // Get player data (should be 'available' for direct assignment)
            $player_stmt = $conn->prepare("SELECT id, name, status FROM players WHERE id = ? FOR UPDATE");
            $player_stmt->bind_param("i", $player_id);
            $player_stmt->execute();
            $player_result = $player_stmt->get_result();
            $player_data = $player_result->fetch_assoc();

            if (!$player_data) {
                throw new Exception("Player not found.");
            }
            if ($player_data['status'] !== 'available') {
                throw new Exception("Player is not available for direct assignment (status: " . $player_data['status'] . ").");
            }

            // The price used is now the admin-provided buying_price
            $assignment_price = $buying_price;

            // Get captain's data (budget and roster)
            $captain_stmt = $conn->prepare("SELECT id, name, budget, team_roster FROM captains WHERE id = ? FOR UPDATE");
            $captain_stmt->bind_param("s", $captain_id);
            $captain_stmt->execute();
            $captain_result = $captain_stmt->get_result();
            $captain_data = $captain_result->fetch_assoc();

            if (!$captain_data) {
                throw new Exception("Target captain profile not found for ID: " . $captain_id);
            }

            $roster_size = (int)getSetting($conn, 'roster_size'); // Get roster size from settings
            $current_roster = json_decode($captain_data['team_roster'], true);
            if (!is_array($current_roster)) { // Ensure it's an array if it was null/empty JSON
                $current_roster = [];
            }

            // Check if captain's roster is full
            if (count($current_roster) >= $roster_size) {
                throw new Exception("Captain's roster is full! Cannot assign more players to " . $captain_data['name'] . ".");
            }

            // Check if captain has enough budget
            if ($captain_data['budget'] < $assignment_price) {
                throw new Exception("Captain " . $captain_data['name'] . " has insufficient budget ($" . $captain_data['budget'] . ") to assign this player (needs $" . $assignment_price . ").");
            }

            // Update player status and current_price with the admin-defined buying_price
            $update_player_stmt = $conn->prepare("UPDATE players SET status = 'drafted', drafted_by = ?, current_bidder_id = NULL, current_price = ? WHERE id = ?");
            $update_player_stmt->bind_param("sdi", $captain_id, $assignment_price, $player_id);
            if (!$update_player_stmt->execute()) {
                throw new Exception("Failed to assign player (player update): " . $conn->error);
            }

            // Update captain's budget and roster
            $new_budget = $captain_data['budget'] - $assignment_price;
            $new_roster = array_merge($current_roster, [$player_id]);
            $new_roster_json = json_encode(array_values($new_roster)); // Re-index array after merge

            $update_captain_stmt = $conn->prepare("UPDATE captains SET budget = ?, team_roster = ? WHERE id = ?");
            $update_captain_stmt->bind_param("dss", $new_budget, $new_roster_json, $captain_id);
            if (!$update_captain_stmt->execute()) {
                throw new Exception("Failed to assign player (captain update): " . $conn->error);
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => $player_data['name'] . ' successfully assigned to ' . $captain_data['name'] . ' for $' . number_format($assignment_price, 2) . '!']);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

$conn->close();
?>