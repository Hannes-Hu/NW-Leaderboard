<?php
require_once '../db.php';
$db = new DB;
$user = $db->getUserOrRedirect();

$matchweek_id = $_GET['id'] ?? null;
if (!$matchweek_id || !is_numeric($matchweek_id)) {
    header("Location: matchweeks.php");
    exit;
}

// Get matchweek details
$matchweek_result = $db->mysqli->query("SELECT * FROM matchweeks WHERE matchweek_id = $matchweek_id");
if ($matchweek_result->num_rows === 0) {
    header("Location: matchweeks.php");
    exit;
}
$matchweek = $matchweek_result->fetch_assoc();

// Get current player data
$current_players = $db->mysqli->query("
    SELECT guid, name, total_kills, total_deaths, total_assists, total_damage_dealt, 
           total_damage_taken, total_rounds_played, first_picks, first_deaths 
    FROM leaderboard
")->fetch_all(MYSQLI_ASSOC);

// Get snapshot data
$snapshot_data = json_decode($matchweek['snapshot_data'], true);
$stats = [];

// Calculate matchweek stats by comparing current data with snapshot
foreach ($current_players as $player) {
    $guid = $player['guid'];
    
    if (isset($snapshot_data[$guid])) {
        $snapshot = $snapshot_data[$guid];
        
        $stats[] = [
            'name' => $player['name'],
            'kills' => $player['total_kills'] - $snapshot['total_kills'],
            'deaths' => $player['total_deaths'] - $snapshot['total_deaths'],
            'assists' => $player['total_assists'] - $snapshot['total_assists'],
            'damage_dealt' => $player['total_damage_dealt'] - $snapshot['total_damage_dealt'],
            'damage_taken' => $player['total_damage_taken'] - $snapshot['total_damage_taken'],
            'rounds_played' => $player['total_rounds_played'] - $snapshot['total_rounds_played'],
            'first_picks' => $player['first_picks'] - $snapshot['first_picks'],
            'first_deaths' => $player['first_deaths'] - $snapshot['first_deaths']
        ];
    }
}

// Sort by kills descending
usort($stats, function($a, $b) {
    return $b['kills'] <=> $a['kills'];
});

$title = 'Matchweek Statistics: ' . htmlspecialchars($matchweek['name']);
$page = 'admin';

$body = '
<div class="phase">
    <div class="phase-header">
        <span>MATCHWEEK STATISTICS: '.htmlspecialchars($matchweek['name']).'</span>
        <small>'.date('Y-m-d H:i', strtotime($matchweek['start_date'])).' to '.date('Y-m-d H:i', strtotime($matchweek['end_date'])).'</small>
    </div>
    
    <div style="padding: 20px;">
        <a href="matchweeks.php" class="btn" style="margin-bottom: 20px;">&larr; Back to Matchweeks</a>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Player</th>
                    <th>Kills</th>
                    <th>Deaths</th>
                    <th>Assists</th>
                    <th>Damage Dealt</th>
                    <th>Damage Taken</th>
                    <th>Rounds Played</th>
                    <th>First Picks</th>
                    <th>First Deaths</th>
                </tr>
            </thead>
            <tbody>';

foreach ($stats as $stat) {
    // Only show players who actually played in this matchweek
    if ($stat['rounds_played'] > 0) {
        $body .= '
                <tr>
                    <td>'.htmlspecialchars($stat['name']).'</td>
                    <td>'.$stat['kills'].'</td>
                    <td>'.$stat['deaths'].'</td>
                    <td>'.$stat['assists'].'</td>
                    <td>'.$stat['damage_dealt'].'</td>
                    <td>'.$stat['damage_taken'].'</td>
                    <td>'.$stat['rounds_played'].'</td>
                    <td>'.$stat['first_picks'].'</td>
                    <td>'.$stat['first_deaths'].'</td>
                </tr>';
    }
}

$body .= '
            </tbody>
        </table>
        
        <div style="margin-top: 20px;">
            <a href="matchweeks.php" class="btn">&larr; Back to Matchweeks</a>
        </div>
    </div>
</div>';

require '../page.php';