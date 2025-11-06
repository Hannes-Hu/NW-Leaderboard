<?php
require '../db.php';
$db = new DB;

$neededIPv4 = $db->getSetting('ipv4');
$neededToken = $db->getSetting('token');

header('Content-type: text/plain');

const RESPONSE_ERROR = -1;
const RESPONSE_IGNORE = 0;
const RESPONSE_OK     = 1;

const TYPE_TEAMCHECK = 1;

// Scene ID to Map Name
$SCENE_NAMES = [
    162 => "BYT Desert",
    163 => "BYT City",
    164 => "BYT Snow",
    165 => "BYT Stadium",
    166 => "BYT Forest",
    167 => "Seeding Tournament",
    168 => "BYT Island",
    169 => "Practice Map"
];

function getMapName($scene_id) {
    global $SCENE_NAMES;
    return $SCENE_NAMES[(int)$scene_id] ?? 'unknown';
}

try {
    $token = $_GET['token'] ?? null;
    $type = $_GET['type'] ?? null;
    $scene_id = isset($_GET['map']) ? (int)$_GET['map'] : 0;
    $map_name = getMapName($scene_id);

    // Debug logging (remove in production)
    file_put_contents('api_debug.log', 
        date('Y-m-d H:i:s') . " | Type: $type | Map ID: $scene_id | Map Name: $map_name\n" . 
        "GET: " . print_r($_GET, true) . "\n\n",
        FILE_APPEND
    );

    if (($neededIPv4 === $_SERVER['REMOTE_ADDR'] || $neededIPv4 === '*') && $neededToken === $token) {

        if ($type === 'log') {
            if (isset($_GET['sourceid'], $_GET['targetid'], $_GET['logtype'], $_GET['value'])) {
                $source_id = $_GET['sourceid'];
                $target_id = $_GET['targetid'];
                $log_type = $_GET['logtype'];
                $value = $_GET['value'];
                
                // Handle assists
                if (isset($_GET['assist'])) {
                    $db->playerIncrementField($_GET['assist'], 'assists');
                    // Update map stats for assists
                    $db->mysqli->query("
                        INSERT INTO map_stats (guid, map_name, assists) 
                        VALUES ('" . $db->mysqli->real_escape_string($_GET['assist']) . "', '" . $db->mysqli->real_escape_string($map_name) . "', 1)
                        ON DUPLICATE KEY UPDATE assists = assists + 1
                    ");
                }
                
                switch ($log_type) {
                    case 'teamkill':
                        $db->playerIncrementField($source_id, 'teamkills', $value);
                        $db->playerIncrementField($target_id, 'teamdeaths', $value);
                        break;
                    case 'kill':
                        $db->playerIncrementField($source_id, 'kills', $value);
                        $db->playerIncrementField($target_id, 'deaths', $value);
                        // Update map stats for kills and deaths
                        $db->mysqli->query("
                            INSERT INTO map_stats (guid, map_name, kills) 
                            VALUES ('" . $db->mysqli->real_escape_string($source_id) . "', '" . $db->mysqli->real_escape_string($map_name) . "', $value)
                            ON DUPLICATE KEY UPDATE kills = kills + $value
                        ");
                        $db->mysqli->query("
                            INSERT INTO map_stats (guid, map_name, deaths) 
                            VALUES ('" . $db->mysqli->real_escape_string($target_id) . "', '" . $db->mysqli->real_escape_string($map_name) . "', $value)
                            ON DUPLICATE KEY UPDATE deaths = deaths + $value
                        ");
                        break;
                    case 'teamdamage':
                        $db->playerIncrementField($source_id, 'friendly_damage_dealt', $value);
                        $db->playerIncrementField($target_id, 'friendly_damage_taken', $value);
                        break;
                    case 'damage':
                        $db->playerIncrementField($source_id, 'damage_dealt', $value);
                        $db->playerIncrementField($target_id, 'damage_taken', $value);
                        // Update map stats for damage
                        $db->mysqli->query("
                            INSERT INTO map_stats (guid, map_name, damage_dealt) 
                            VALUES ('" . $db->mysqli->real_escape_string($source_id) . "', '" . $db->mysqli->real_escape_string($map_name) . "', $value)
                            ON DUPLICATE KEY UPDATE damage_dealt = damage_dealt + $value
                        ");
                        $db->mysqli->query("
                            INSERT INTO map_stats (guid, map_name, damage_taken) 
                            VALUES ('" . $db->mysqli->real_escape_string($target_id) . "', '" . $db->mysqli->real_escape_string($map_name) . "', $value)
                            ON DUPLICATE KEY UPDATE damage_taken = damage_taken + $value
                        ");
                        break;
                    case 'round':
                        $db->playerIncrementField($source_id, 'rounds_played', $value);
                        // Update map stats for rounds played
                        $db->mysqli->query("
                            INSERT INTO map_stats (guid, map_name, rounds_played) 
                            VALUES ('" . $db->mysqli->real_escape_string($source_id) . "', '" . $db->mysqli->real_escape_string($map_name) . "', $value)
                            ON DUPLICATE KEY UPDATE rounds_played = rounds_played + $value
                        ");
                        break;
                }
                
                echo RESPONSE_IGNORE;
            }
        } 
        else if ($type === 'welcome') {
            if (isset($_GET['id'], $_GET['name'])) {
                $db->playerWelcome($_GET['id'], $_GET['name']);
                echo RESPONSE_IGNORE;
            }
        } 
        else if ($type === 'teamcheck') {
            if (isset($_GET['id'], $_GET['team'])) {
                $team = $_GET['team'];
                $guid = $_GET['id'];
                
                if ($team === "1" || $team === "2") {
                    $whitelist = $db->getSetting("whitelist-team-$team");
                    $ok = empty($whitelist) ? 1 : (in_array($guid, $whitelist) ? 1 : 0);
                    echo RESPONSE_OK . '|' . TYPE_TEAMCHECK . "|$guid|$team|$ok";
                }
            }
        } 
        else {
            throw new Exception('Invalid request type');
        }
    } else {
        throw new Exception('Auth error');
    }
} catch (Exception $e) {
    file_put_contents('api_errors.log', date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n", FILE_APPEND);
    echo RESPONSE_ERROR . '|' . get_class($e) . ' => ' . $e->getMessage();
}