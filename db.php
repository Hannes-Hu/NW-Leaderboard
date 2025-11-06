<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class DB {
    public mysqli $mysqli;
    private static $host = 'localhost';
    private static $user = 'Redacted';
    private static $pass = 'Redacted';
    private static $database = 'u578436281_leaderboard';
    
    public function __construct() {
        $this->mysqli = new mysqli(self::$host, self::$user, self::$pass, self::$database);
    }
    
    public function getUser() {
        if (isset($_COOKIE['token'])) {
            $token = $this->mysqli->real_escape_string($_COOKIE['token']);
            
            $res = $this->mysqli->query("SELECT * FROM `tokens` WHERE `value` LIKE '$token' LIMIT 1");
            
            if ($res->num_rows === 1) {
                $user_id = $res->fetch_assoc()['user_id'];
                
                $res = $this->mysqli->query("SELECT user_id, username FROM `users` WHERE user_id='$user_id' LIMIT 1");
                
                if ($res->num_rows === 1) {
                    return $res->fetch_assoc();
                }
            }
        }
        
        return null;
    }
    
    public function getUserOrRedirect() {
        $user = $this->getUser();
        
        if ($user !== null) {
            return $user;
        } else {
            header("Location: /admin/login.php");
            exit;
        }
    }
    
    public function logIn($user, $pass) {
        $user = $this->mysqli->real_escape_string($user);
        
        $res = $this->mysqli->query("SELECT * FROM `users` WHERE username='$user' LIMIT 1");
        
        if ($res->num_rows === 1) {
            $userData = $res->fetch_assoc();
            if (password_verify($pass, $userData['password'])) {
                $user_id = $userData['user_id'];
                $token = base64_encode(openssl_random_pseudo_bytes(40));
                
                $this->mysqli->query("INSERT INTO `tokens` (`token_id`, `user_id`, `value`) VALUES (NULL, '$user_id', '$token')");
                
                setcookie('token', $token, time()+60*60*24*30);
                
                return true;
            }
        }
        return false;
    }
    
    public function changePassword($user_id, $new_pass) {
        $new_pass_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $new_pass_hash = $this->mysqli->real_escape_string($new_pass_hash);
        
        $this->mysqli->query("UPDATE `users` SET `password`='$new_pass_hash' WHERE `user_id`='$user_id'");
    }
    
    public function getSetting($setting) {
        $setting = $this->mysqli->real_escape_string($setting);
        
        $res = $this->mysqli->query("SELECT * FROM `settings` WHERE setting='$setting' LIMIT 1");
        
        if ($res->num_rows === 1) {
            return json_decode($res->fetch_assoc()['value'], true);
        }
        
        return null;
    }
    
    public function setSetting($setting, $value) {
        $setting = $this->mysqli->real_escape_string($setting);
        $value = $this->mysqli->real_escape_string(json_encode($value));
        
        $this->mysqli->query("INSERT INTO `settings` (`setting`, `value`) VALUES ('$setting', '$value') ON DUPLICate KEY UPDATE value='$value'");
    }
    
    // No automatic namechange on join
    
    public function playerWelcome($guid, $name) {
        $guid = $this->mysqli->real_escape_string($guid);
        $name = $this->mysqli->real_escape_string($name);
    
        // Modified line: Removed 'name='$name'' from ON DUPLICATE KEY UPDATE
        $this->mysqli->query("INSERT INTO `leaderboard` (`guid`, `name`, `last_login`) VALUES ('$guid', '$name', CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE last_login=CURRENT_TIMESTAMP()");
        $this->mysqli->query("INSERT INTO `name_history` (`guid`, `name`, `last_used`) VALUES ('$guid', '$name', CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE last_used=CURRENT_TIMESTAMP()");
    }
    
    public function playerIncrementField($guid, $field, $value='1') {
        if ($guid > 0) {
            $value = $this->mysqli->real_escape_string($value);
            $this->mysqli->query("UPDATE `leaderboard` SET total_$field = total_$field + $value WHERE guid = '$guid'");
        }
    }
    
    // In the getPlayers() method, update the SELECT query to include nationality:
    public function getPlayers($page=1, $page_size=50, $search=null, $sort=null) {
        // Fix: Handle null search parameter
        $search = $search ?? '';
        $search = is_string($search) ? $this->mysqli->real_escape_string($search) : '';
        
        $player_search = "(guid = '$search') OR (name LIKE '%$search%')";
        $player_count = $this->mysqli->query("SELECT COUNT(*) AS count FROM `leaderboard` WHERE $player_search")->fetch_assoc()['count'];
        
        // Get ALL players first (not paginated yet)
        $players = $this->mysqli->query("SELECT guid, name, total_kills, total_deaths, total_teamkills, total_teamdeaths, total_assists, total_damage_dealt, total_damage_taken, total_friendly_damage_dealt, total_friendly_damage_taken, total_rounds_played, first_picks, first_deaths, nationality FROM `leaderboard` WHERE $player_search")->fetch_all(MYSQLI_ASSOC);
        
        // Calculate ratings for all players
        foreach ($players as &$player) {
            $rating_value = 0;
            if ($player['total_rounds_played'] > 0) {
                $rating_value = (
                    ($player['total_assists'] / $player['total_rounds_played'] / 2) + 
                    ($player['total_kills'] / $player['total_rounds_played']) - 
                    ($player['total_deaths'] / $player['total_rounds_played']) + 
                    ($player['total_damage_dealt'] / $player['total_rounds_played'] / 100) + 
                    (($player['first_picks'] / $player['total_rounds_played']) - ($player['first_deaths'] / $player['total_rounds_played'])) + 
                    7.5 - 
                    ($player['total_damage_taken'] / $player['total_rounds_played'] / 1000)
                );
                
                $rating_value = min(9.99, max(0, $rating_value)) * 10;
            }
            
            $player['calculated_rating'] = $rating_value;
        }
        
        // Sort players by rating in descending order
        usort($players, function($a, $b) {
            return $b['calculated_rating'] <=> $a['calculated_rating'];
        });
        
        // Now apply pagination
        $offset = ($page - 1) * $page_size;
        $paginated_players = array_slice($players, $offset, $page_size);
        
        // Add rank numbers
        $rank = $offset + 1;
        foreach ($paginated_players as &$player) {
            $player['rank'] = $rank++;
        }
        
        return [$player_count, $paginated_players];
    }
        
    public function clearLeaderboard($prefix) {
        $this->mysqli->query("UPDATE `leaderboard` SET {$prefix}_kills=DEFAULT, {$prefix}_deaths=DEFAULT, {$prefix}_teamkills=DEFAULT WHERE 1");
    }
    
    public function getPlayerAliases($search) {
        $search = $this->mysqli->real_escape_string($search);
        
        $foundGuids = $this->mysqli->query("SELECT guid FROM `name_history` WHERE BINARY name='$search'")->fetch_all(MYSQLI_ASSOC);
        $foundGuids[] = ['guid' => $search];
        
        $aliases = [];
        foreach($foundGuids as $guid) {
            $aliases = array_merge($aliases, $this->mysqli->query("SELECT * FROM `name_history` WHERE guid='{$guid['guid']}'")->fetch_all(MYSQLI_ASSOC));
        }
        
        return $aliases;
    }
    
    public function getPlayerData($guid) {
        $guid = $this->mysqli->real_escape_string($guid);
        $res = $this->mysqli->query("SELECT * FROM leaderboard WHERE guid='$guid' LIMIT 1");
        return $res->num_rows ? $res->fetch_assoc() : null;
    }

    public function getPlayerMapStats($guid) {
        $guid = $this->mysqli->real_escape_string($guid);
        $map_stats = $this->mysqli->query("
            SELECT 
                map_name,
                kills,
                deaths,
                damage_dealt,
                damage_taken,
                assists,
                rounds_played,
                first_picks,
                first_deaths
            FROM map_stats 
            WHERE guid='$guid'
            ORDER BY rounds_played DESC, kills DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        // Calculate rating for each map
        foreach ($map_stats as &$map) {
            if ($map['rounds_played'] > 0) {
                $rating_value = (
                    ($map['assists'] / $map['rounds_played'] / 2) + 
                    ($map['kills'] / $map['rounds_played']) - 
                    ($map['deaths'] / $map['rounds_played']) + 
                    ($map['damage_dealt'] / $map['rounds_played'] / 100) + 
                    (($map['first_picks'] / $map['rounds_played']) - ($map['first_deaths'] / $map['rounds_played'])) + 
                    7.5 - 
                    ($map['damage_taken'] / $map['rounds_played'] / 1000)
                );
                
                $rating_value = min(9.99, max(0, $rating_value)) * 10;
                $map['rating'] = round($rating_value, 1);
            } else {
                $map['rating'] = 'N/A';
            }
        }
        
        return $map_stats;
    }
    
    public function updateMapStat($guid, $map_name, $field, $value = 1) {
        $guid = $this->mysqli->real_escape_string($guid);
        $map = $this->mysqli->real_escape_string($map_name);
        $field = $this->mysqli->real_escape_string($field);
        $value = (int)$value;
        
        // Use proper SQL syntax for INSERT ON DUPLICATE KEY UPDATE
        $this->mysqli->query("
            INSERT INTO map_stats (guid, map_name, $field) 
            VALUES ('$guid', '$map', $value)
            ON DUPLICATE KEY UPDATE $field = $field + $value
        ");
        
        return $this->mysqli->affected_rows > 0;
    }
    
    public function getPlayerMapRatings($guid) {
        $guid = $this->mysqli->real_escape_string($guid);
        
        $map_stats = $this->mysqli->query("
            SELECT 
                map_name,
                kills,
                deaths,
                damage_dealt,
                damage_taken,
                assists,
                rounds_played,
                first_picks,
                first_deaths
            FROM map_stats 
            WHERE guid='$guid'
            ORDER BY rounds_played DESC, kills DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        $ratings = [];
        foreach ($map_stats as $map) {
            if ($map['rounds_played'] > 0) {
                $rating_value = (
                    ($map['assists'] / $map['rounds_played'] / 2) + 
                    ($map['kills'] / $map['rounds_played']) - 
                    ($map['deaths'] / $map['rounds_played']) + 
                    ($map['damage_dealt'] / $map['rounds_played'] / 100) + 
                    (($map['first_picks'] / $map['rounds_played']) - ($map['first_deaths'] / $map['rounds_played'])) + 
                    7.5 - 
                    ($map['damage_taken'] / $map['rounds_played'] / 1000)
                );
                
                $rating_value = min(9.99, max(0, $rating_value)) * 10;
                $ratings[$map['map_name']] = round($rating_value, 1);
            } else {
                $ratings[$map['map_name']] = 'N/A';
            }
        }
        
        return $ratings;
    }
    
    // Team Statistics Methods
    public function getAvailableTeams() {
        $query = "SELECT DISTINCT 
                    CASE 
                        WHEN name LIKE '%\_%' THEN SUBSTRING_INDEX(name, '_', 1)
                        ELSE 'No Tag'
                    END AS team_tag
                  FROM leaderboard
                  WHERE total_rounds_played > 0
                  ORDER BY team_tag";
        $result = $this->mysqli->query($query);
        $teams = [];
        while ($row = $result->fetch_assoc()) {
            $teams[] = $row['team_tag'];
        }
        return $teams;
    }
    
    public function getAvailableMaps() {
        $query = "SELECT DISTINCT map_name FROM map_stats ORDER BY map_name";
        $result = $this->mysqli->query($query);
        $maps = [];
        while ($row = $result->fetch_assoc()) {
            $maps[] = $row['map_name'];
        }
        return $maps;
    }
    
    public function getTeamStatistics($team_filter = '', $map_filter = 'Total', $sort_by = 'rating', $sort_order = 'desc') {
        // Validate sort parameters
        $valid_sort_columns = ['player_count', 'total_kills', 'total_deaths', 'kd_ratio', 
                              'total_assists', 'damage_dealt', 'damage_taken', 'rounds_played', 'rating'];
        $sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'rating';
        $sort_order = strtolower($sort_order) === 'asc' ? 'asc' : 'desc';
        
        $params = [];
        
        if ($map_filter === 'Total') {
            // Total statistics across all maps
            $query = "SELECT 
                        CASE 
                            WHEN l.name LIKE '%\_%' THEN SUBSTRING_INDEX(l.name, '_', 1)
                        ELSE 'No Tag'
                        END AS team_tag,
                        COUNT(*) as player_count,
                        SUM(l.total_kills) as total_kills,
                        SUM(l.total_deaths) as total_deaths,
                        SUM(l.total_assists) as total_assists,
                        SUM(l.total_damage_dealt) as damage_dealt,
                        SUM(l.total_damage_taken) as damage_taken,
                        SUM(l.total_rounds_played) as rounds_played,
                        SUM(l.first_picks) as first_picks,
                        SUM(l.first_deaths) as first_deaths,
                        ROUND(SUM(l.total_kills) / NULLIF(SUM(l.total_deaths), 0), 2) as kd_ratio,
                        CASE 
                            WHEN SUM(l.total_rounds_played) > 0 THEN
                                ROUND((
                                    (SUM(l.total_assists) / SUM(l.total_rounds_played) / 2) + 
                                    (SUM(l.total_kills) / SUM(l.total_rounds_played)) - 
                                    (SUM(l.total_deaths) / SUM(l.total_rounds_played)) + 
                                    (SUM(l.total_damage_dealt) / SUM(l.total_rounds_played) / 100) + 
                                    ((SUM(l.first_picks) / SUM(l.total_rounds_played)) - (SUM(l.first_deaths) / SUM(l.total_rounds_played))) + 
                                    7.5 - 
                                    (SUM(l.total_damage_taken) / SUM(l.total_rounds_played) / 1000)
                                ) * 10, 1)
                            ELSE 'N/A'
                        END as rating
                      FROM leaderboard l
                      WHERE l.total_rounds_played > 0";
        } else {
            // Map-specific statistics
            $query = "SELECT 
                        CASE 
                            WHEN l.name LIKE '%\_%' THEN SUBSTRING_INDEX(l.name, '_', 1)
                            ELSE 'No Tag'
                        END AS team_tag,
                        COUNT(DISTINCT m.guid) as player_count,
                        SUM(m.kills) as total_kills,
                        SUM(m.deaths) as total_deaths,
                        SUM(m.assists) as total_assists,
                        SUM(m.damage_dealt) as damage_dealt,
                        SUM(m.damage_taken) as damage_taken,
                        SUM(m.rounds_played) as rounds_played,
                        SUM(m.first_picks) as first_picks,
                        SUM(m.first_deaths) as first_deaths,
                        ROUND(SUM(m.kills) / NULLIF(SUM(m.deaths), 0), 2) as kd_ratio,
                        CASE 
                            WHEN SUM(m.rounds_played) > 0 THEN
                                ROUND((
                                    (SUM(m.assists) / SUM(m.rounds_played) / 2) + 
                                    (SUM(m.kills) / SUM(m.rounds_played)) - 
                                    (SUM(m.deaths) / SUM(m.rounds_played)) + 
                                    (SUM(m.damage_dealt) / SUM(m.rounds_played) / 100) + 
                                    ((SUM(m.first_picks) / SUM(m.rounds_played)) - (SUM(m.first_deaths) / SUM(m.rounds_played))) + 
                                    7.5 - 
                                    (SUM(m.damage_taken) / SUM(m.rounds_played) / 1000)
                                ) * 10, 1)
                            ELSE 'N/A'
                        END as rating
                      FROM map_stats m
                      JOIN leaderboard l ON m.guid = l.guid
                      WHERE m.map_name = '" . $this->mysqli->real_escape_string($map_filter) . "'";
            $params[':map_name'] = $map_filter;
        }
        
        // Add team filter if specified
        if (!empty($team_filter)) {
            $query .= " AND l.name LIKE '" . $this->mysqli->real_escape_string($team_filter) . "_%'";
        }
        
        $query .= " GROUP BY team_tag
                    HAVING player_count >= 1
                    ORDER BY $sort_by $sort_order";
        
        $result = $this->mysqli->query($query);
        $team_stats = [];
        while ($row = $result->fetch_assoc()) {
            $team_stats[] = $row;
        }
        return $team_stats;
    }
    
    // === NEW METHODS FOR MATCHWEEK SNAPSHOT APPROACH ===
    
    /**
     * Get the currently active matchweek
     */
    public function getCurrentMatchweek() {
        $res = $this->mysqli->query("SELECT * FROM `matchweeks` WHERE is_active = 1 LIMIT 1");
        return $res->num_rows ? $res->fetch_assoc() : null;
    }
    
    /**
     * Get all matchweeks
     */
    public function getAllMatchweeks() {
        $res = $this->mysqli->query("SELECT * FROM `matchweeks` ORDER BY start_date DESC");
        return $res->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Capture a snapshot of current stats for a matchweek
     */
    public function captureMatchweekSnapshot($matchweek_id) {
        $matchweek_id = (int)$matchweek_id;
        
        // Capture player stats
        $players = $this->mysqli->query("
            SELECT guid, total_kills, total_deaths, total_assists, total_damage_dealt, 
                   total_damage_taken, total_rounds_played, first_picks, first_deaths 
            FROM leaderboard
        ");
        
        while ($player = $players->fetch_assoc()) {
            $guid = (int)$player['guid'];
            $kills = (int)$player['total_kills'];
            $deaths = (int)$player['total_deaths'];
            $assists = (int)$player['total_assists'];
            $damage_dealt = (int)$player['total_damage_dealt'];
            $damage_taken = (int)$player['total_damage_taken'];
            $rounds_played = (int)$player['total_rounds_played'];
            $first_picks = (int)$player['first_picks'];
            $first_deaths = (int)$player['first_deaths'];
            
            $this->mysqli->query("
                INSERT INTO matchweek_snapshots 
                (matchweek_id, guid, kills, deaths, assists, damage_dealt, damage_taken, rounds_played, first_picks, first_deaths)
                VALUES 
                ($matchweek_id, $guid, $kills, $deaths, $assists, $damage_dealt, $damage_taken, $rounds_played, $first_picks, $first_deaths)
                ON DUPLICATE KEY UPDATE
                kills = VALUES(kills), deaths = VALUES(deaths), assists = VALUES(assists),
                damage_dealt = VALUES(damage_dealt), damage_taken = VALUES(damage_taken),
                rounds_played = VALUES(rounds_played), first_picks = VALUES(first_picks), first_deaths = VALUES(first_deaths)
            ");
        }
        
        // Capture map stats
        $map_stats = $this->mysqli->query("
            SELECT guid, map_name, kills, deaths, damage_dealt, damage_taken, assists, 
                   rounds_played, first_picks, first_deaths 
            FROM map_stats
        ");
        
        while ($map_stat = $map_stats->fetch_assoc()) {
            $guid = (int)$map_stat['guid'];
            $map_name = $this->mysqli->real_escape_string($map_stat['map_name']);
            $kills = (int)$map_stat['kills'];
            $deaths = (int)$map_stat['deaths'];
            $damage_dealt = (int)$map_stat['damage_dealt'];
            $damage_taken = (int)$map_stat['damage_taken'];
            $assists = (int)$map_stat['assists'];
            $rounds_played = (int)$map_stat['rounds_played'];
            $first_picks = (int)$map_stat['first_picks'];
            $first_deaths = (int)$map_stat['first_deaths'];
            
            $this->mysqli->query("
                INSERT INTO matchweek_map_snapshots 
                (matchweek_id, guid, map_name, kills, deaths, damage_dealt, damage_taken, assists, rounds_played, first_picks, first_deaths)
                VALUES 
                ($matchweek_id, $guid, '$map_name', $kills, $deaths, $damage_dealt, $damage_taken, $assists, $rounds_played, $first_picks, $first_deaths)
                ON DUPLICATE KEY UPDATE
                kills = VALUES(kills), deaths = VALUES(deaths), damage_dealt = VALUES(damage_dealt),
                damage_taken = VALUES(damage_taken), assists = VALUES(assists), rounds_played = VALUES(rounds_played),
                first_picks = VALUES(first_picks), first_deaths = VALUES(first_deaths)
            ");
        }
        
        return true;
    }
    
    /**
     * Reset leaderboard stats to zero but keep GUIDs, names, and nationality
     */
    public function resetLeaderboardStats() {
        // Reset all stats to zero but keep GUIDs, names, and nationality
        $this->mysqli->query("
            UPDATE leaderboard SET 
            total_kills = 0, total_deaths = 0, total_teamkills = 0, total_teamdeaths = 0,
            total_assists = 0, total_damage_dealt = 0, total_damage_taken = 0,
            total_friendly_damage_dealt = 0, total_friendly_damage_taken = 0,
            total_rounds_played = 0, first_picks = 0, first_deaths = 0
        ");
        
        // Also reset map stats
        $this->mysqli->query("
            UPDATE map_stats SET 
            kills = 0, deaths = 0, damage_dealt = 0, damage_taken = 0, assists = 0,
            rounds_played = 0, first_picks = 0, first_deaths = 0
        ");
        
        return true;
    }
    
    /**
     * Get players for a specific matchweek
     */
    public function getMatchweekPlayers($matchweek_id, $page = 1, $page_size = 50, $search = null, $division = 'all', $division_prefixes = []) {
        $matchweek_id = (int)$matchweek_id;
        $search = $search ?? '';
        $search = is_string($search) ? $this->mysqli->real_escape_string($search) : '';
        
        $player_search = $search ? "AND (l.guid = '$search' OR l.name LIKE '%$search%')" : "";
        
        // Build division condition
        $division_condition = '';
        if ($division !== 'all' && isset($division_prefixes[$division])) {
            $division_conditions = [];
            foreach ($division_prefixes[$division] as $prefix) {
                $escaped_prefix = $this->mysqli->real_escape_string($prefix);
                $division_conditions[] = "l.name LIKE '$escaped_prefix%'";
            }
            if (!empty($division_conditions)) {
                $division_condition = "AND (" . implode(" OR ", $division_conditions) . ")";
            }
        }
        
        // Get player count
        $count_result = $this->mysqli->query("
            SELECT COUNT(*) as count 
            FROM matchweek_snapshots ms
            JOIN leaderboard l ON ms.guid = l.guid
            WHERE ms.matchweek_id = $matchweek_id $player_search $division_condition
        ");
        $player_count = $count_result->fetch_assoc()['count'];
        
        // Get players with pagination - SORT BY RATING INSTEAD OF KILLS
        $offset = ($page - 1) * $page_size;
        $players_result = $this->mysqli->query("
            SELECT ms.*, l.name, l.nationality,
                   (
                       (ms.assists / GREATEST(ms.rounds_played, 1) / 2) + 
                       (ms.kills / GREATEST(ms.rounds_played, 1)) - 
                       (ms.deaths / GREATEST(ms.rounds_played, 1)) + 
                       (ms.damage_dealt / GREATEST(ms.rounds_played, 1) / 100) + 
                       ((ms.first_picks / GREATEST(ms.rounds_played, 1)) - (ms.first_deaths / GREATEST(ms.rounds_played, 1))) + 
                       7.5 - 
                       (ms.damage_taken / GREATEST(ms.rounds_played, 1) / 1000)
                   ) as calculated_rating
            FROM matchweek_snapshots ms
            JOIN leaderboard l ON ms.guid = l.guid
            WHERE ms.matchweek_id = $matchweek_id $player_search $division_condition
            ORDER BY 
            CASE WHEN ms.rounds_played > 0 THEN 1 ELSE 0 END DESC,
            calculated_rating DESC
            LIMIT $offset, $page_size
        ");
        
        $players = $players_result->fetch_all(MYSQLI_ASSOC);
        
        // Calculate ratings
        foreach ($players as &$player) {
            $rating_value = 0;
            if ($player['rounds_played'] > 0) {
                $rating_value = (
                    ($player['assists'] / $player['rounds_played'] / 2) + 
                    ($player['kills'] / $player['rounds_played']) - 
                    ($player['deaths'] / $player['rounds_played']) + 
                    ($player['damage_dealt'] / $player['rounds_played'] / 100) + 
                    (($player['first_picks'] / $player['rounds_played']) - ($player['first_deaths'] / $player['rounds_played'])) + 
                    7.5 - 
                    ($player['damage_taken'] / $player['rounds_played'] / 1000)
                );
                
                $rating_value = min(9.99, max(0, $rating_value)) * 10;
            }
            
            $player['calculated_rating'] = $rating_value;
            $player['kd_ratio'] = round($player['kills'] / max(1, $player['deaths']), 2);
        }
        
        // Add rank numbers
        $rank = $offset + 1;
        foreach ($players as &$player) {
            $player['rank'] = $rank++;
        }
        
        return [$player_count, $players];
    }
    
    public function getTotalPlayers($page = 1, $page_size = 50, $search = null, $division = 'all', $division_prefixes = []) {
        $search = $search ?? '';
        $search = is_string($search) ? $this->mysqli->real_escape_string($search) : '';
        
        // Build the search condition
        $search_condition = '';
        if ($search) {
            $search_condition = "AND (leaderboard.guid = '$search' OR leaderboard.name LIKE '%$search%')";
        }
        
        // Build division condition
        $division_condition = '';
        if ($division !== 'all' && isset($division_prefixes[$division])) {
            $division_conditions = [];
            foreach ($division_prefixes[$division] as $prefix) {
                $escaped_prefix = $this->mysqli->real_escape_string($prefix);
                $division_conditions[] = "leaderboard.name LIKE '$escaped_prefix%'";
            }
            if (!empty($division_conditions)) {
                $division_condition = "AND (" . implode(" OR ", $division_conditions) . ")";
            }
        }
        
        // Get player count
        $count_result = $this->mysqli->query("
            SELECT COUNT(*) as count 
            FROM leaderboard 
            WHERE 1=1 $search_condition $division_condition
        ");
        $player_count = $count_result->fetch_assoc()['count'];
        
        // Get all matchweek stats summed up for each player
        $offset = ($page - 1) * $page_size;
        $players_result = $this->mysqli->query("
            SELECT 
                leaderboard.guid,
                leaderboard.name,
                leaderboard.nationality,
                COALESCE(SUM(ms.kills), 0) as total_kills,
                COALESCE(SUM(ms.deaths), 0) as total_deaths,
                COALESCE(SUM(ms.assists), 0) as total_assists,
                COALESCE(SUM(ms.damage_dealt), 0) as total_damage_dealt,
                COALESCE(SUM(ms.damage_taken), 0) as total_damage_taken,
                COALESCE(SUM(ms.rounds_played), 0) as total_rounds_played,
                COALESCE(SUM(ms.first_picks), 0) as first_picks,
                COALESCE(SUM(ms.first_deaths), 0) as first_deaths,
                CASE 
                    WHEN COALESCE(SUM(ms.rounds_played), 0) > 0 THEN
                        (
                            (COALESCE(SUM(ms.assists), 0) / COALESCE(SUM(ms.rounds_played), 1) / 2) + 
                            (COALESCE(SUM(ms.kills), 0) / COALESCE(SUM(ms.rounds_played), 1)) - 
                            (COALESCE(SUM(ms.deaths), 0) / COALESCE(SUM(ms.rounds_played), 1)) + 
                            (COALESCE(SUM(ms.damage_dealt), 0) / COALESCE(SUM(ms.rounds_played), 1) / 100) + 
                            ((COALESCE(SUM(ms.first_picks), 0) / COALESCE(SUM(ms.rounds_played), 1)) - (COALESCE(SUM(ms.first_deaths), 0) / COALESCE(SUM(ms.rounds_played), 1))) + 
                            7.5 - 
                            (COALESCE(SUM(ms.damage_taken), 0) / COALESCE(SUM(ms.rounds_played), 1) / 1000)
                        ) * 10
                    ELSE 0
                END as calculated_rating
            FROM leaderboard
            LEFT JOIN matchweek_snapshots ms ON leaderboard.guid = ms.guid
            WHERE 1=1 $search_condition $division_condition
            GROUP BY leaderboard.guid, leaderboard.name, leaderboard.nationality
            ORDER BY 
                CASE WHEN COALESCE(SUM(ms.rounds_played), 0) > 0 THEN 1 ELSE 0 END DESC,
                calculated_rating DESC
            LIMIT $offset, $page_size
        ");
        
        $players = $players_result->fetch_all(MYSQLI_ASSOC);
        
        // Calculate ratings properly for display
        foreach ($players as &$player) {
            $rating_value = 0;
            if ($player['total_rounds_played'] > 0) {
                $rating_value = (
                    ($player['total_assists'] / $player['total_rounds_played'] / 2) + 
                    ($player['total_kills'] / $player['total_rounds_played']) - 
                    ($player['total_deaths'] / $player['total_rounds_played']) + 
                    ($player['total_damage_dealt'] / $player['total_rounds_played'] / 100) + 
                    (($player['first_picks'] / $player['total_rounds_played']) - ($player['first_deaths'] / $player['total_rounds_played'])) + 
                    7.5 - 
                    ($player['total_damage_taken'] / $player['total_rounds_played'] / 1000)
                );
                
                $rating_value = min(9.99, max(0, $rating_value)) * 10;
                $player['calculated_rating'] = round($rating_value, 1);
            } else {
                $player['calculated_rating'] = 'N/A';
            }
            
            $player['kd_ratio'] = $player['total_deaths'] > 0 
                ? round($player['total_kills'] / $player['total_deaths'], 2)
                : ($player['total_kills'] > 0 ? $player['total_kills'] : 0);
        }
        
        // Add rank numbers - only rank players with actual ratings
        $rank = $offset + 1;
        foreach ($players as &$player) {
            if ($player['calculated_rating'] !== 'N/A') {
                $player['rank'] = $rank++;
            } else {
                $player['rank'] = 'N/A';
            }
        }
        
        return [$player_count, $players];
    }
    
    /**
     * Get player map statistics for a specific matchweek
     */
    public function getPlayerMatchweekMapStats($guid, $matchweek_id) {
        $guid = (int)$guid;
        $matchweek_id = (int)$matchweek_id;
        
        $stats = $this->mysqli->query("
            SELECT map_name, kills, deaths, damage_dealt, damage_taken, assists, 
                   rounds_played, first_picks, first_deaths
            FROM matchweek_map_snapshots
            WHERE guid = $guid AND matchweek_id = $matchweek_id
            ORDER BY rounds_played DESC, kills DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        // Calculate rating for each map
        foreach ($stats as &$map) {
            if ($map['rounds_played'] > 0) {
                $rating_value = (
                    ($map['assists'] / $map['rounds_played'] / 2) + 
                    ($map['kills'] / $map['rounds_played']) - 
                    ($map['deaths'] / $map['rounds_played']) + 
                    ($map['damage_dealt'] / $map['rounds_played'] / 100) + 
                    (($map['first_picks'] / $map['rounds_played']) - ($map['first_deaths'] / $map['rounds_played'])) + 
                    7.5 - 
                    ($map['damage_taken'] / $map['rounds_played'] / 1000)
                );
                
                $rating_value = min(9.99, max(0, $rating_value)) * 10;
                $map['rating'] = round($rating_value, 1);
            } else {
                $map['rating'] = 'N/A';
            }
        }
        
        return $stats;
    }
    
    /**
     * Get player statistics for all matchweeks
     */
    public function getPlayerMatchweekStats($guid) {
        $guid = (int)$guid;
        
        $stats = $this->mysqli->query("
            SELECT m.name as matchweek_name, ms.*
            FROM matchweek_snapshots ms
            JOIN matchweeks m ON ms.matchweek_id = m.matchweek_id
            WHERE ms.guid = $guid
            ORDER BY m.start_date DESC
        ")->fetch_all(MYSQLI_ASSOC);
        
        return $stats;
    }

}
