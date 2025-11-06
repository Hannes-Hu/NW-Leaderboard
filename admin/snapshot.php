<?php
require_once '../db.php';
$db = new DB;
$user = $db->getUserOrRedirect();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matchweek_id'])) {
    $matchweek_id = (int)$_POST['matchweek_id'];
    
    // Capture snapshot of current stats
    $db->captureMatchweekSnapshot($matchweek_id);
    
    // Reset leaderboard stats to zero (but keep GUIDs, names, nationality)
    $db->resetLeaderboardStats();
    
    header("Location: snapshot.php?success=1");
    exit;
}

// Get all matchweeks
$matchweeks = $db->getAllMatchweeks();

$title = 'Capture Matchweek Snapshot';
$page = 'admin';

$body = '
<div class="phase">
    <div class="phase-header">
        <span>CAPTURE MATCHWEEK SNAPSHOT</span>
    </div>
    
    <div style="padding: 20px;">
        '.(isset($_GET['success']) ? '<div class="success-message">Snapshot captured successfully! Leaderboard has been reset.</div>' : '').'
        
        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label for="matchweek_id" style="display: block; margin-bottom: 8px; font-weight: 600;">Select Matchweek:</label>
                <select name="matchweek_id" id="matchweek_id" required style="padding: 10px; width: 100%; max-width: 300px;">
                    <option value="">-- Select Matchweek --</option>';
                    
foreach ($matchweeks as $matchweek) {
    $body .= '<option value="'.$matchweek['matchweek_id'].'">'.$matchweek['name'].'</option>';
}

$body .= '
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <p><strong>Warning:</strong> This will capture the current statistics for the selected matchweek and reset the leaderboard to zero. This action cannot be undone.</p>
            </div>
            
            <button type="submit" style="padding: 12px 24px; background-color: #d32f2f; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">
                Capture Snapshot & Reset Leaderboard
            </button>
        </form>
    </div>
</div>

<style>
.success-message {
    background-color: #4caf50;
    color: white;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
}
</style>';

require '../page.php';