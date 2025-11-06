<?php
require_once 'db.php';
$db = new DB;

$page = $_GET['page'] ?? 1;
if (!filter_var($page, FILTER_VALIDATE_INT) || $page < 1) {
    $page = 1;
}

$search_query = $_GET['search'] ?? null;
$selected_matchweek = $_GET['matchweek'] ?? 'all';
$selected_division = $_GET['division'] ?? 'all';

// Define divisions
$divisions = [
    'all' => 'All Divisions',
    'division1' => 'Division 1',
    'division2' => 'Division 2', 
    'division3' => 'Division 3',
    'division4' => 'Division 4',
    'division5' => 'Division 5'
];

// Division team prefixes
$division_prefixes = [
    'division1' => ['G13_', 'DAB_', 'PE_', 'KV_'],
    'division2' => ['BMS_', 'GVT_', 'FruitNinjas_', 'NCT_'],
    'division3' => ['GP_', 'IRAN_', 'V1bers_'],
    'division4' => ['Assassins_', 'Inter_', 'SS_', 'ESX_'],
    'division5' => ['24thBav_', 'SubV_', '1stKB_', 'Nr31_']
];

// Get matchweek information
$matchweeks = $db->getAllMatchweeks();
$current_matchweek = $db->getCurrentMatchweek();

$body = '
<div class="phase">
    <div class="phase-header">
        <span>PLAYER STATISTICS</span>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <form class="search-form" method="GET" style="margin: 0;">
                <input type="text" class="search-input" placeholder="Search players..." name="search" value="'.htmlspecialchars($search_query ?? '').'">
                <input type="hidden" name="matchweek" value="'.$selected_matchweek.'">
                <input type="hidden" name="division" value="'.$selected_division.'">
                <button type="submit" class="search-button">Search</button>
            </form>
            
            <form method="GET" class="filter-selector">
                <select name="matchweek" onchange="this.form.submit()">
                    <option value="all" '.($selected_matchweek === 'all' ? 'selected' : '').'>Total</option>';
                    
foreach ($matchweeks as $matchweek) {
    $body .= '<option value="'.$matchweek['matchweek_id'].'" '.($selected_matchweek == $matchweek['matchweek_id'] ? 'selected' : '').'>'.$matchweek['name'].'</option>';
}

$body .= '
                </select>
                <input type="hidden" name="search" value="'.htmlspecialchars($search_query ?? '').'">
                <input type="hidden" name="division" value="'.$selected_division.'">
            </form>
            
            <form method="GET" class="filter-selector">
                <select name="division" onchange="this.form.submit()">
                    <option value="all" '.($selected_division === 'all' ? 'selected' : '').'>All Divisions</option>
                    <option value="division1" '.($selected_division === 'division1' ? 'selected' : '').'>Division 1</option>
                    <option value="division2" '.($selected_division === 'division2' ? 'selected' : '').'>Division 2</option>
                    <option value="division3" '.($selected_division === 'division3' ? 'selected' : '').'>Division 3</option>
                    <option value="division4" '.($selected_division === 'division4' ? 'selected' : '').'>Division 4</option>
                    <option value="division5" '.($selected_division === 'division5' ? 'selected' : '').'>Division 5</option>
                </select>
                <input type="hidden" name="search" value="'.htmlspecialchars($search_query ?? '').'">
                <input type="hidden" name="matchweek" value="'.$selected_matchweek.'">
            </form>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Player</th>
                <th class="stats hide-mobile">Rounds</th>
                <th class="stats">Kills</th>
                <th class="stats">Deaths</th>
                <th class="stats">K/D</th>
                <th class="stats hide-mobile">Assists</th>
                <th class="stats hide-mobile">Damage Dealt</th>
                <th class="stats hide-mobile">Damage Taken</th>
                <th class="stats">Rating</th>
            </tr>
        </thead>
        <tbody>';

// Get data based on selected matchweek and division
if ($selected_matchweek === 'all') {
    // Use the new getTotalPlayers method with division filter
    $result = $db->getTotalPlayers($page, 15, $search_query, $selected_division, $division_prefixes);
} else {
    // Use the new getMatchweekPlayers method with division filter
    $result = $db->getMatchweekPlayers($selected_matchweek, $page, 15, $search_query, $selected_division, $division_prefixes);
}

$total_players = $result[0];
$players = $result[1];

foreach ($players as $player) {
    // Format rating
    $rating = 'N/A';
    $rating_class = '';
    
    // Check if calculated_rating exists and is numeric (not 'N/A')
    if (isset($player['calculated_rating']) && is_numeric($player['calculated_rating']) && $player['calculated_rating'] > 0) {
        $rating_value = (float)$player['calculated_rating'];
        $rounded_rating = round($rating_value, 1);
        
        // Add color coding based on rating
        if ($rounded_rating >= 90.0) $rating_class = 'rating-best';
        elseif ($rounded_rating >= 85.0) $rating_class = 'rating-2nd-best';
        elseif ($rounded_rating >= 80.0) $rating_class = 'rating-3rd-best';
        elseif ($rounded_rating >= 75.0) $rating_class = 'rating-4th-best';
        elseif ($rounded_rating >= 70.0) $rating_class = 'rating-5th-best';
        else $rating_class = 'rating-worst';
        
        $rating = '<span class="' . $rating_class . '">' . $rounded_rating . '</span>';
    } else {
        // Handle cases where rating is 'N/A' or not numeric
        $rating = 'N/A';
        $rating_class = 'rating-na';
    }

    // Get nationality for this player
    $nationality = $player['nationality'] ?? '';
    
    // Add country flag if available
    $player_name_display = htmlspecialchars($player['name']);
    if (!empty($nationality)) {
        $flag_path = "/flags_lb/" . $nationality . ".png";
        $player_name_display = '<img src="' . $flag_path . '" alt="' . $nationality . '" class="country-flag" title="' . $nationality . '"> ' . $player_name_display;
    }

    // Use the appropriate field names based on whether we're viewing a specific matchweek or all time
    $rounds_played = $selected_matchweek === 'all' ? $player['total_rounds_played'] : $player['rounds_played'];
    $kills = $selected_matchweek === 'all' ? $player['total_kills'] : $player['kills'];
    $deaths = $selected_matchweek === 'all' ? $player['total_deaths'] : $player['deaths'];
    $assists = $selected_matchweek === 'all' ? $player['total_assists'] : $player['assists'];
    $damage_dealt = $selected_matchweek === 'all' ? $player['total_damage_dealt'] : $player['damage_dealt'];
    $damage_taken = $selected_matchweek === 'all' ? $player['total_damage_taken'] : $player['damage_taken'];
    
    // Calculate KD ratio safely
    if ($selected_matchweek === 'all') {
        $kd_ratio = is_numeric($player['kd_ratio']) ? round($player['kd_ratio'], 2) : 'N/A';
    } else {
        $kd_ratio = ($deaths > 0) ? round($kills / $deaths, 2) : ($kills > 0 ? $kills : 'N/A');
    }

    $body .= '
            <tr>
                <td>'.$player['rank'].'</td>
                <td><a href="/profile.php?id='.$player['guid'].'&matchweek='.$selected_matchweek.(!empty($search_query) ? '&search='.urlencode($search_query) : '').'&division='.$selected_division.'" class="team-name">'.$player_name_display.'</a></td>
                <td class="stats hide-mobile">'.$rounds_played.'</td>
                <td class="stats">'.$kills.'</td>
                <td class="stats">'.$deaths.'</td>
                <td class="stats">'.$kd_ratio.'</td>
                <td class="stats hide-mobile">'.$assists.'</td>
                <td class="stats hide-mobile">'.$damage_dealt.'</td>
                <td class="stats hide-mobile">'.$damage_taken.'</td>
                <td class="stats">'.$rating.'</td>
            </tr>';
}

$body .= '
        </tbody>
    </table>
    <div class="pagination">';

$page_count = ceil($total_players / 15);
if ($page_count == 0) $page_count = 1;

function makeButton($text, $page=null, $active=false) {
    global $selected_matchweek, $selected_division, $search_query;
    
    if ($page === null) {
        return '<span class="page-button">'.$text.'</span>';
    } else {
        $href = '?page='.$page.'&matchweek='.$selected_matchweek.'&division='.$selected_division.(isset($search_query) ? '&search='.$search_query : '');
        return '<a href="'.$href.'" class="page-button'.($active ? ' active' : '').'">'.$text.'</a>';
    }
}

$body .= makeButton('«', $page != 1 ? $page-1 : $page, true);

if ($page > 2) {
    $body .= makeButton('1', 1, $page==1);
    if ($page > 3) {
        $body .= makeButton('...');
    }
}

$offset = 1;
if ($page == 1 || $page == $page_count) {
    $offset = 2;
}
for ($i=$page-$offset; $i<=$page+$offset; $i++) {
    if ($i > 0 && $i <= $page_count) {
        $body .= makeButton($i, $i, $i==$page);
    }
}

if ($page < $page_count-1) {
    if ($page < $page_count-2) {
        $body .= makeButton('...');
    }
    $body .= makeButton($page_count, $page_count, $page==$page_count);
}
$body .= makeButton('»', $page_count != $page ? $page+1 : $page, true);

$body .= '
    </div>
</div>';

// Add CSS for rating colors, flags, and filter selectors
$body .= '
<style>
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
    border: 1px solid #ddd;
    border-radius: 2px;
    object-fit: cover;
}
.team-name {
    display: flex;
    align-items: center;
}
.filter-selector select {
    padding: 8px 12px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    background-color: rgba(0, 0, 0, 0.3);
    color: white;
    font-size: 14px;
}
.filter-selector select:focus {
    outline: none;
    border-color: rgba(255, 255, 255, 0.4);
}
</style>';

$title = 'Leaderboard';
$page = 'leaderboard';
$search = true;
$search_placeholder = 'Search players...';

require 'page.php';