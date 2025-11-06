<?php
require_once '../db.php';
$db = new DB;
$user = $db->getUserOrRedirect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_matchweek'])) {
        $name = $_POST['name'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        $db->createMatchweek($name, $start_date, $end_date);
        header("Location: matchweeks.php?success=1");
        exit;
    }
}

$matchweeks = $db->getAllMatchweeks();
$current_matchweek = $db->getCurrentMatchweek();

$title = 'Manage Matchweeks';
$page = 'admin';
require '../page.php';

$body = '
<div class="phase">
    <div class="phase-header">
        <span>MANAGE MATCHWEEKS</span>
    </div>
    
    <div style="padding: 20px;">
        <h2>Create New Matchweek</h2>
        <form method="POST" class="admin-form">
            <div class="form-group">
                <label for="name">Matchweek Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="datetime-local" id="start_date" name="start_date" required>
            </div>
            
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="datetime-local" id="end_date" name="end_date" required>
            </div>
            
            <button type="submit" name="create_matchweek" class="btn btn-primary">Create Matchweek</button>
        </form>
        
        <h2 style="margin-top: 40px;">Existing Matchweeks</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

foreach ($matchweeks as $matchweek) {
    $is_active = $matchweek['is_active'] ? '<span style="color: green;">Active</span>' : 'Inactive';
    
    $body .= '
                <tr>
                    <td>'.$matchweek['matchweek_id'].'</td>
                    <td>'.htmlspecialchars($matchweek['name']).'</td>
                    <td>'.date('Y-m-d H:i', strtotime($matchweek['start_date'])).'</td>
                    <td>'.date('Y-m-d H:i', strtotime($matchweek['end_date'])).'</td>
                    <td>'.$is_active.'</td>
                    <td>
                        <a href="matchweek_stats.php?id='.$matchweek['matchweek_id'].'" class="btn btn-sm">View Stats</a>
                    </td>
                </tr>';
}

$body .= '
            </tbody>
        </table>
    </div>
</div>';

// Add admin-specific styles
$head = '
<style>
    .admin-form {
        max-width: 600px;
        margin-bottom: 30px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    
    .form-group input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        background-color: rgba(0, 0, 0, 0.3);
        color: white;
    }
    
    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .admin-table th {
        background-color: rgba(255, 255, 255, 0.1);
        padding: 12px 10px;
        text-align: left;
        color: var(--white-secondary);
        border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    .admin-table td {
        padding: 12px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .admin-table tr {
        background-color: rgba(255, 255, 255, 0.03);
    }
    
    .admin-table tr:nth-child(even) {
        background-color: rgba(255, 255, 255, 0.05);
    }
    
    .admin-table tr:hover {
        background-color: rgba(255, 255, 255, 0.08);
    }
    
    .btn {
        display: inline-block;
        padding: 8px 16px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        text-decoration: none;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #4ecdc4, #007bff);
        border: none;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #3bbbb3, #0069d9);
    }
    
    .btn-sm {
        padding: 4px 8px;
        font-size: 0.85rem;
    }
</style>';

require '../page.php';