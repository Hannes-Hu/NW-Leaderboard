<?php
require_once 'db.php';
$db = new DB;

$guid = $_GET['id'] ?? null;
$matchweek_id = $_GET['matchweek'] ?? null;
// Add these lines to capture the search and matchweek filters
$search_query = $_GET['search'] ?? '';
$selected_matchweek = $_GET['matchweek'] ?? 'all';

if (!$guid || !is_numeric($guid)) {
    header("Location: /");
    exit;
}

$player = $db->getPlayerData($guid);
if (!$player) {
    header("Location: /");
    exit;
}

// Get appropriate stats based on whether we're viewing a specific matchweek or all time
if ($matchweek_id && $matchweek_id !== 'all') {
    $matchweek_stats = $db->getPlayerMatchweekStats($guid);
    $current_matchweek_stats = null;
    
    // Find the specific matchweek stats
    foreach ($matchweek_stats as $stats) {
        if ($stats['matchweek_id'] == $matchweek_id) {
            $current_matchweek_stats = $stats;
            break;
        }
    }
    
    // Get map stats for this matchweek
    $map_stats = $db->getPlayerMatchweekMapStats($guid, $matchweek_id);
    
    // Use matchweek stats for display
    $display_stats = [
        'kills' => $current_matchweek_stats['kills'] ?? 0,
        'deaths' => $current_matchweek_stats['deaths'] ?? 0,
        'assists' => $current_matchweek_stats['assists'] ?? 0,
        'damage_dealt' => $current_matchweek_stats['damage_dealt'] ?? 0,
        'damage_taken' => $current_matchweek_stats['damage_taken'] ?? 0,
        'rounds_played' => $current_matchweek_stats['rounds_played'] ?? 0,
        'first_picks' => $current_matchweek_stats['first_picks'] ?? 0,
        'first_deaths' => $current_matchweek_stats['first_deaths'] ?? 0
    ];
} else {
    // Get all-time stats (sum of all matchweeks)
    $map_stats = $db->getPlayerMapStats($guid);
    $matchweek_stats = $db->getPlayerMatchweekStats($guid);
    
    // Calculate total stats from all matchweeks
    $total_stats = [
        'kills' => 0,
        'deaths' => 0,
        'assists' => 0,
        'damage_dealt' => 0,
        'damage_taken' => 0,
        'rounds_played' => 0,
        'first_picks' => 0,
        'first_deaths' => 0
    ];
    
    foreach ($matchweek_stats as $stats) {
        $total_stats['kills'] += $stats['kills'];
        $total_stats['deaths'] += $stats['deaths'];
        $total_stats['assists'] += $stats['assists'];
        $total_stats['damage_dealt'] += $stats['damage_dealt'];
        $total_stats['damage_taken'] += $stats['damage_taken'];
        $total_stats['rounds_played'] += $stats['rounds_played'];
        $total_stats['first_picks'] += $stats['first_picks'];
        $total_stats['first_deaths'] += $stats['first_deaths'];
    }
    
    $display_stats = $total_stats;
}

// Calculate overall rating
$overall_rating = 'N/A';
if ($display_stats['rounds_played'] > 0) {
    $rating_value = (
        ($display_stats['assists'] / $display_stats['rounds_played'] / 2) + 
        ($display_stats['kills'] / $display_stats['rounds_played']) - 
        ($display_stats['deaths'] / $display_stats['rounds_played']) + 
        ($display_stats['damage_dealt'] / $display_stats['rounds_played'] / 100) + 
        (($display_stats['first_picks'] / $display_stats['rounds_played']) - ($display_stats['first_deaths'] / $display_stats['rounds_played'])) + 
        7.5 - 
        ($display_stats['damage_taken'] / $display_stats['rounds_played'] / 1000)
    );
    
    $rating_value = min(9.99, max(0, $rating_value)) * 10;
    $overall_rating = round($rating_value, 1);
    
    // Add color coding based on new rating scheme
    $rating_class = '';
    if ($overall_rating >= 90.0) $rating_class = 'rating-best';
    elseif ($overall_rating >= 85.0) $rating_class = 'rating-2nd-best';
    elseif ($overall_rating >= 80.0) $rating_class = 'rating-3rd-best';
    elseif ($overall_rating >= 75.0) $rating_class = 'rating-4th-best';
    elseif ($overall_rating >= 70.0) $rating_class = 'rating-5th-best';
    else $rating_class = 'rating-worst';
    
    $overall_rating_display = '<span class="' . $rating_class . '">' . $overall_rating . '</span>';
} else {
    $overall_rating_display = 'N/A';
}

// Calculate first duel win rate
$first_duel_win_rate = 'N/A';
if (($display_stats['first_picks'] + $display_stats['first_deaths']) > 0) {
    $first_duel_win_rate = round(($display_stats['first_picks'] / ($display_stats['first_picks'] + $display_stats['first_deaths'])) * 100, 1) . '%';
}

// Add country flag if available
$player_name_display = htmlspecialchars($player['name']);
if (!empty($player['nationality'])) {
    $flag_path = "/flags_lb/" . $player['nationality'] . ".png";
    $player_name_display = '<img src="' . $flag_path . '" alt="' . $player['nationality'] . '" class="country-flag" title="' . $player['nationality'] . '"> ' . $player_name_display;
}

// Get matchweek name if viewing a specific matchweek
$matchweek_name = '';
if ($matchweek_id && $matchweek_id !== 'all') {
    $all_matchweeks = $db->getAllMatchweeks();
    foreach ($all_matchweeks as $mw) {
        if ($mw['matchweek_id'] == $matchweek_id) {
            $matchweek_name = ' - ' . htmlspecialchars($mw['name']);
            break;
        }
    }
}

// Create the back URL with preserved filters
$back_url = "/?matchweek=" . urlencode($selected_matchweek);
if (!empty($search_query)) {
    $back_url .= "&search=" . urlencode($search_query);
}

$body = '
<div class="phase">
    <div class="phase-header">
        <span class="player-name-with-flag">'.$player_name_display.$matchweek_name.'</span>
        <small>ID: '.$guid.'</small>
    </div>
    
    <div style="padding: 20px;">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">'.$display_stats['rounds_played'].'</div>
                <div class="stat-label">Rounds Played</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">'.$display_stats['kills'].'</div>
                <div class="stat-label">Kills</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">'.$display_stats['deaths'].'</div>
                <div class="stat-label">Deaths</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">'.round($display_stats['kills']/max(1,$display_stats['deaths']), 2).'</div>
                <div class="stat-label">K/D Ratio</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">'.$display_stats['assists'].'</div>
                <div class="stat-label">Assists</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">'.$display_stats['damage_dealt'].'</div>
                <div class="stat-label">Damage Dealt</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">'.$display_stats['damage_taken'].'</div>
                <div class="stat-label">Damage Taken</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">'.$display_stats['first_picks'].' / '.$display_stats['first_deaths'].'</div>
                <div class="stat-label">First Duels (W/L)</div>
                <div class="stat-subtext">'.$first_duel_win_rate.' win rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">'.$overall_rating_display.'</div>
                <div class="stat-label">Rating</div>
            </div>
        </div>';
        
// Add matchweek performance section if data exists
if (!empty($matchweek_stats) && $matchweek_id === 'all') {
    $body .= '
        <h3 style="margin: 30px 0 15px; color: var(--white-primary);">Matchweek Performance</h3>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Matchweek</th>
                    <th>Rounds</th>
                    <th>Kills</th>
                    <th>Deaths</th>
                    <th>Assists</th>
                    <th>K/D</th>
                    <th>Damage Dealt</th>
                    <th>Damage Taken</th>
                    <th>First Duels</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($matchweek_stats as $matchweek) {
        $kd_ratio = round($matchweek['kills'] / max(1, $matchweek['deaths']), 2);
        
        // Calculate first duel win rate for this matchweek
        $matchweek_first_duel_win_rate = 'N/A';
        $matchweek_first_picks = $matchweek['first_picks'] ?? 0;
        $matchweek_first_deaths = $matchweek['first_deaths'] ?? 0;
        if (($matchweek_first_picks + $matchweek_first_deaths) > 0) {
            $matchweek_first_duel_win_rate = round(($matchweek_first_picks / ($matchweek_first_picks + $matchweek_first_deaths)) * 100, 1) . '%';
        }
        
        // Calculate rating for this matchweek
        $matchweek_rating = 'N/A';
        if ($matchweek['rounds_played'] > 0) {
            $rating_value = (
                ($matchweek['assists'] / $matchweek['rounds_played'] / 2) + 
                ($matchweek['kills'] / $matchweek['rounds_played']) - 
                ($matchweek['deaths'] / $matchweek['rounds_played']) + 
                ($matchweek['damage_dealt'] / $matchweek['rounds_played'] / 100) + 
                (($matchweek_first_picks / $matchweek['rounds_played']) - ($matchweek_first_deaths / $matchweek['rounds_played'])) + 
                7.5 - 
                ($matchweek['damage_taken'] / $matchweek['rounds_played'] / 1000)
            );
            
            $rating_value = min(9.99, max(0, $rating_value)) * 10;
            $matchweek_rating = round($rating_value, 1);
            
            // Add color coding
            $rating_class = '';
            if ($matchweek_rating >= 90.0) $rating_class = 'rating-best';
            elseif ($matchweek_rating >= 85.0) $rating_class = 'rating-2nd-best';
            elseif ($matchweek_rating >= 80.0) $rating_class = 'rating-3rd-best';
            elseif ($matchweek_rating >= 75.0) $rating_class = 'rating-4th-best';
            elseif ($matchweek_rating >= 70.0) $rating_class = 'rating-5th-best';
            else $rating_class = 'rating-worst';
            
            $matchweek_rating = '<span class="' . $rating_class . '">' . $matchweek_rating . '</span>';
        }
        
        $body .= '
                <tr>
                    <td>'.htmlspecialchars($matchweek['matchweek_name']).'</td>
                    <td>'.$matchweek['rounds_played'].'</td>
                    <td>'.$matchweek['kills'].'</td>
                    <td>'.$matchweek['deaths'].'</td>
                    <td>'.($matchweek['assists'] ?? 0).'</td>
                    <td>'.$kd_ratio.'</td>
                    <td>'.$matchweek['damage_dealt'].'</td>
                    <td>'.$matchweek['damage_taken'].'</td>
                    <td title="'.$matchweek_first_picks.' wins / '.$matchweek_first_deaths.' losses ('.$matchweek_first_duel_win_rate.')">
                        '.$matchweek_first_picks.'/'.$matchweek_first_deaths.'
                    </td>
                    <td>'.$matchweek_rating.'</td>
                </tr>';
    }
    
    $body .= '
            </tbody>
        </table>';
}

$body .= '
        <h3 style="margin: 30px 0 15px; color: var(--white-primary);">Map Performance</h3>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Map</th>
                    <th>Rounds</th>
                    <th>Kills</th>
                    <th>Deaths</th>
                    <th>Assists</th>
                    <th>K/D</th>
                    <th>Damage Dealt</th>
                    <th>Damage Taken</th>
                    <th>First Duels</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>';

foreach ($map_stats as $map) {
    $kd_ratio = round($map['kills'] / max(1, $map['deaths']), 2);
    
    // Calculate first duel win rate for this map
    $map_first_duel_win_rate = 'N/A';
    $map_first_picks = $map['first_picks'] ?? 0;
    $map_first_deaths = $map['first_deaths'] ?? 0;
    if (($map_first_picks + $map_first_deaths) > 0) {
        $map_first_duel_win_rate = round(($map_first_picks / ($map_first_picks + $map_first_deaths)) * 100, 1) . '%';
    }
    
    // Add color coding based on new rating scheme
    $rating_class = '';
    if ($map['rating'] !== 'N/A') {
        if ($map['rating'] >= 90.0) $rating_class = 'rating-best';
        elseif ($map['rating'] >= 85.0) $rating_class = 'rating-2nd-best';
        elseif ($map['rating'] >= 80.0) $rating_class = 'rating-3rd-best';
        elseif ($map['rating'] >= 75.0) $rating_class = 'rating-4th-best';
        elseif ($map['rating'] >= 70.0) $rating_class = 'rating-5th-best';
        else $rating_class = 'rating-worst';
        
        $map_rating = '<span class="' . $rating_class . '">' . $map['rating'] . '</span>';
    } else {
        $map_rating = 'N/A';
    }
    
    $body .= '
                <tr>
                    <td>'.htmlspecialchars($map['map_name']).'</td>
                    <td>'.$map['rounds_played'].'</td>
                    <td>'.$map['kills'].'</td>
                    <td>'.$map['deaths'].'</td>
                    <td>'.($map['assists'] ?? 0).'</td>
                    <td>'.$kd_ratio.'</td>
                    <td>'.$map['damage_dealt'].'</td>
                    <td>'.$map['damage_taken'].'</td>
                    <td title="'.$map_first_picks.' wins / '.$map_first_deaths.' losses ('.$map_first_duel_win_rate.')">
                        '.$map_first_picks.'/'.$map_first_deaths.'
                    </td>
                    <td>'.$map_rating.'</td>
                </tr>';
}

$body .= '
            </tbody>
        </table>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="' . $back_url . '" class="back-button">&larr; Back to Leaderboard</a>
        </div>
    </div>
</div>';

// Add profile-specific styles
$head = '
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background-color: rgba(255, 255, 255, 0.03);
        border-radius: var(--border-radius);
        padding: 15px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: var(--transition);
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--box-shadow);
        background-color: rgba(255, 255, 255, 0.08);
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: 600;
        color: var(--white-primary);
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin-bottom: 3px;
    }
    
    .stat-subtext {
        font-size: 0.8rem;
        color: var(--text-muted);
        font-style: italic;
        opacity: 0.8;
    }
    
    .stats-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .stats-table th {
        background-color: rgba(255, 255, 255, 0.1);
        padding: 12px 10px;
        text-align: center;
        color: var(--white-secondary);
        border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    .stats-table td {
        padding: 12px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        text-align: center;
    }
    
    .stats-table tr {
        background-color: rgba(255, 255, 255, 0.03);
    }
    
    .stats-table tr:nth-child(even) {
        background-color: rgba(255, 255, 255, 0.05);
    }
    
    .stats-table tr:hover {
        background-color: rgba(255, 255, 255, 0.08);
    }
    
    .back-button {
        display: inline-block;
        padding: 10px 20px;
        background: linear-gradient(135deg, var(--white-primary), var(--white-secondary));
        color: var(--bg-dark);
        text-decoration: none;
        borderRadius: var(--border-radius);
        transition: var(--transition);
        font-weight: 600;
        border: none;
    }
    
    .back-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
    }
    
    /* New rating color scheme */
    .rating-best { 
        color: #7F00FF;
        font-weight: bold; 
    }
    
    .rating-2nd-best { 
        color: #0096FF; 
        font-weight: bold;
    }
    
    .rating-3rd-best { 
        color: #008000; 
        font-weight: bold;
    }
    
    .rating-4th-best {
        font-weight: bold;
    }
    
    .rating-5th-best { 
        color: #FFA500; 
        font-weight: bold;
    }
    
    .rating-worst { 
        color: #F44336;
        font-weight: bold;
    }
    
    .country-flag {
        width: 24px;
        height: 16px;
        vertical-align: middle;
        margin-right: 6px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 2px;
        object-fit: cover;
    }
    
    .player-name-with-flag {
        display: flex;
        align-items: center;
    }
    
    @media (max-width: 1200px) {
        .stats-table th:nth-child(7),
        .stats-table td:nth-child(7),
        .stats-table th:nth-child(8),
        .stats-table td:nth-child(8) {
            display: none;
        }
    }
    
    @media (max-width: 900px) {
        .stats-table th:nth-child(5),
        .stats-table td:nth-child(5),
        .stats-table th:nth-child(6),
        .stats-table td:nth-child(6) {
            display: none;
        }
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .stats-table th:nth-child(4),
        .stats-table td:nth-child(4),
        .stats-table th:nth-child(9),
        .stats-table td:nth-child(9) {
            display: none;
        }
    }
    
    @media (max-width: 480px) {
        .stats-table th:nth-child(3),
        .stats-table td:nth-child(3) {
            display: none;
        }
    }
</style>';

$title = htmlspecialchars($player['name']).' - Profile';
$page = 'profile';
require 'page.php';