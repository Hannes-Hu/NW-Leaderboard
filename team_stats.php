<?php
require_once 'db.php';
$db = new DB;

// Get filter parameters
$team_filter = $_GET['team'] ?? '';
$map_filter = $_GET['map'] ?? 'Total';
$sort_by = $_GET['sort'] ?? 'rating';
$sort_order = $_GET['order'] ?? 'desc';

// Get available teams and maps for dropdowns
$available_teams = $db->getAvailableTeams();
$available_maps = $db->getAvailableMaps();

// Get team statistics based on filters
$team_stats = $db->getTeamStatistics($team_filter, $map_filter, $sort_by, $sort_order);

$body = '
<div class="phase">
    <div class="phase-header">
        <span>TEAM STATISTICS COMPARISON</span>
        <form class="filter-form" method="GET">
            <select name="team" class="filter-select">
                <option value="">All Teams</option>';
foreach ($available_teams as $team) {
    $selected = $team_filter === $team ? 'selected' : '';
    $body .= '<option value="' . htmlspecialchars($team) . '" ' . $selected . '>' . htmlspecialchars($team) . '</option>';
}
$body .= '
            </select>
            
            <select name="map" class="filter-select">
                <option value="Total"' . ($map_filter === 'Total' ? 'selected' : '') . '>All Maps</option>';
foreach ($available_maps as $map) {
    $selected = $map_filter === $map ? 'selected' : '';
    $body .= '<option value="' . htmlspecialchars($map) . '" ' . $selected . '>' . htmlspecialchars($map) . '</option>';
}
$body .= '
            </select>
            
            <button type="submit" class="filter-button">Apply Filters</button>
            <a href="team_stats.php" class="reset-button">Reset</a>
        </form>
    </div>
    
    <div style="padding: 20px;">
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Team Tag</th>
                    <th><a href="?team=' . urlencode($team_filter) . '&map=' . urlencode($map_filter) . '&sort=player_count&order=' . ($sort_by === 'player_count' && $sort_order === 'asc' ? 'desc' : 'asc') . '">Players ' . ($sort_by === 'player_count' ? ($sort_order === 'asc' ? '↑' : '↓') : '') . '</a></th>
                    <th><a href="?team=' . urlencode($team_filter) . '&map=' . urlencode($map_filter) . '&sort=total_kills&order=' . ($sort_by === 'total_kills' && $sort_order === 'asc' ? 'desc' : 'asc') . '">Kills ' . ($sort_by === 'total_kills' ? ($sort_order === 'asc' ? '↑' : '↓') : '') . '</a></th>
                    <th><a href="?team=' . urlencode($team_filter) . '&map=' . urlencode($map_filter) . '&sort=total_deaths&order=' . ($sort_by === 'total_deaths' && $sort_order === 'asc' ? 'desc' : 'asc') . '">Deaths ' . ($sort_by === 'total_deaths' ? ($sort_order === 'asc' ? '↑' : '↓') : '') . '</a></th>
                    <th><a href="?team=' . urlencode($team_filter) . '&map=' . urlencode($map_filter) . '&sort=kd_ratio&order=' . ($sort_by === 'kd_ratio' && $sort_order === 'asc' ? 'desc' : 'asc') . '">K/D Ratio ' . ($sort_by === 'kd_ratio' ? ($sort_order === 'asc' ? '↑' : '↓') : '') . '</a></th>
                    <th class="hide-mobile"><a href="?team=' . urlencode($team_filter) . '&map=' . urlencode($map_filter) . '&sort=total_assists&order=' . ($sort_by === 'total_assists' && $sort_order === 'asc' ? 'desc' : 'asc') . '">Assists ' . ($sort_by === 'total_assists' ? ($sort_order === 'asc' ? '↑' : '↓') : '') . '</a></th>
                    <th class="hide-mobile"><a href="?team=' . urlencode($team_filter) . '&map=' . urlencode($map_filter) . '&sort=damage_dealt&order=' . ($sort_by === 'damage_dealt' && $sort_order === 'asc' ? 'desc' : 'asc') . '">Damage Dealt ' . ($sort_by === 'damage_dealt' ? ($sort_order === 'asc' ? '↑' : '↓') : '') . '</a></th>
                    <th class="hide-mobile"><a href="?team=' . urlencode($team_filter) . '&map=' . urlencode($map_filter) . '&sort=damage_taken&order=' . ($sort_by === 'damage_taken' && $sort_order === 'asc' ? 'desc' : 'asc') . '">Damage Taken ' . ($sort_by === 'damage_taken' ? ($sort_order === 'asc' ? '↑' : '↓') : '') . '</a></th>
                    <th><a href="?team=' . urlencode($team_filter) . '&map=' . urlencode($map_filter) . '&sort=rounds_played&order=' . ($sort_by === 'rounds_played' && $sort_order === 'asc' ? 'desc' : 'asc') . '">Rounds ' . ($sort_by === 'rounds_played' ? ($sort_order === 'asc' ? '↑' : '↓') : '') . '</a></th>
                    <th><a href="?team=' . urlencode($team_filter) . '&map=' . urlencode($map_filter) . '&sort=rating&order=' . ($sort_by === 'rating' && $sort_order === 'asc' ? 'desc' : 'asc') . '">Rating ' . ($sort_by === 'rating' ? ($sort_order === 'asc' ? '↑' : '↓') : '') . '</a></th>
                </tr>
            </thead>
            <tbody>';

foreach ($team_stats as $team) {
    $rating_class = '';
    if ($team['rating'] !== 'N/A') {
        if ($team['rating'] >= 90.0) $rating_class = 'rating-best';
        elseif ($team['rating'] >= 85.0) $rating_class = 'rating-2nd-best';
        elseif ($team['rating'] >= 80.0) $rating_class = 'rating-3rd-best';
        elseif ($team['rating'] >= 75.0) $rating_class = 'rating-4th-best';
        elseif ($team['rating'] >= 70.0) $rating_class = 'rating-5th-best';
        else $rating_class = 'rating-worst';
        
        $rating_display = '<span class="' . $rating_class . '">' . $team['rating'] . '</span>';
    } else {
        $rating_display = 'N/A';
    }
    
    $body .= '
                <tr>
                    <td><strong>' . htmlspecialchars($team['team_tag']) . '</strong></td>
                    <td>' . $team['player_count'] . '</td>
                    <td>' . $team['total_kills'] . '</td>
                    <td>' . $team['total_deaths'] . '</td>
                    <td>' . $team['kd_ratio'] . '</td>
                    <td class="hide-mobile">' . $team['total_assists'] . '</td>
                    <td class="hide-mobile">' . number_format($team['damage_dealt']) . '</td>
                    <td class="hide-mobile">' . number_format($team['damage_taken']) . '</td>
                    <td>' . $team['rounds_played'] . '</td>
                    <td>' . $rating_display . '</td>
                </tr>';
}

$body .= '
            </tbody>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background-color: rgba(255, 255, 255, 0.03); border-radius: var(--border-radius);">
            <h4 style="margin: 0 0 10px 0; color: var(--white-primary);">Filter Info:</h4>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                ' . ($team_filter ? 'Team: <strong>' . htmlspecialchars($team_filter) . '</strong>' : 'Showing all teams') . ' | 
                ' . ($map_filter !== 'Total' ? 'Map: <strong>' . htmlspecialchars($map_filter) . '</strong>' : 'All maps') . ' | 
                Showing ' . count($team_stats) . ' teams
            </p>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="/" class="back-button">&larr; Back to Leaderboard</a>
        </div>
    </div>
</div>';

// Add team stats specific styles
$head = '
<style>
    .filter-form {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .filter-select {
        padding: 8px 12px;
        border: 1px solid var(--white-secondary);
        border-radius: var(--border-radius);
        background-color: var(--bg-darker);
        color: var(--text-light);
        font-family: \'Montserrat\', sans-serif;
    }
    
    .filter-button {
        padding: 8px 16px;
        background-color: var(--white-primary);
        color: var(--bg-dark);
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: var(--transition);
        font-family: \'Montserrat\', sans-serif;
        font-weight: 600;
    }
    
    .filter-button:hover {
        background-color: var(--white-accent);
        transform: translateY(-2px);
    }
    
    .reset-button {
        padding: 8px 16px;
        background-color: rgba(255, 255, 255, 0.1);
        color: var(--text-light);
        border: 1px solid var(--white-secondary);
        border-radius: var(--border-radius);
        text-decoration: none;
        transition: var(--transition);
        font-family: \'Montserrat\', sans-serif;
        font-weight: 600;
    }
    
    .reset-button:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    .stats-table th a {
        color: var(--white-secondary);
        text-decoration: none;
        display: block;
        padding: 12px 10px;
    }
    
    .stats-table th a:hover {
        color: var(--white-primary);
    }
    
    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-select, .filter-button, .reset-button {
            width: 100%;
            margin-bottom: 5px;
        }
    }
</style>';

$title = 'Team Statistics';
$page = 'team_stats';
require 'page.php';