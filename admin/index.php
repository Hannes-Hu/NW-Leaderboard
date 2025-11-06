<?php
require '../db.php';
$db = new DB;
$user = $db->getUserOrRedirect();

$page = 'admin';
$title = 'Admin Panel';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ipv4'])) {
        $db->setSetting('ipv4', $_POST['ipv4']);
    }

    if (isset($_POST['token'])) {
        $db->setSetting('token', $_POST['token']);
    }

    if (isset($_POST['title'])) {
        $db->setSetting('title', $_POST['title']);
    }

    if (isset($_POST['leaderboard'])) {
        if (in_array('reset', $_POST['leaderboard'])) {
            echo "xxx";
        }
    }

    if (isset($_POST['whitelist-team-1'])) {
        $db->setSetting('whitelist-team-1', explode("\r\n", $_POST['whitelist-team-1']));
    } else {
        $db->setSetting('whitelist-team-1', []);
    }

    if (isset($_POST['whitelist-team-2'])) {
        $db->setSetting('whitelist-team-2', explode("\r\n", $_POST['whitelist-team-2']));
    } else {
        $db->setSetting('whitelist-team-2', []);
    }
}

$ipv4 = htmlspecialchars($db->getSetting('ipv4'));
$token = htmlspecialchars($db->getSetting('token'));
$title_text = htmlspecialchars($db->getSetting('title'));

$whitelist_team_1_array = $db->getSetting('whitelist-team-1') ?: [];
$whitelist_team_1 = htmlspecialchars(implode("\n", $whitelist_team_1_array));
$whitelist_team_2_array = $db->getSetting('whitelist-team-2') ?: [];
$whitelist_team_2 = htmlspecialchars(implode("\n", $whitelist_team_2_array));

$head = <<<EOF
<style>
    textarea {
        min-height: 250px;
    }
</style>
EOF;

$body = <<<EOF
<main class="container">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="my-3 p-3 bg-white rounded shadow">
                <form method="POST">
                    <fieldset>
                        <legend>General settings</legend>
                        
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Website Title</span>
                            </div>
                            <input type="text" name="title" class="form-control" placeholder="Title of the website" value="$title_text" required />
                        </div>
                        
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">IPv4 address</span>
                            </div>
                            <input type="text" name="ipv4" class="form-control" placeholder="IPv4 address of the game server" value="$ipv4" required />
                        </div>
                        
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Auth token</span>
                            </div>
                            <input type="text" name="token" class="form-control" placeholder="Auth token" value="$token" required />
                        </div>
                    </fieldset>
                    
                    <!--<fieldset>
                        <legend>Leaderboard</legend>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="reset" id="resetLeaderbaord" name="leaderboard[]" />
                            <label class="form-check-label" for="resetLeaderbaord">
                                Reset Leaderboard
                            </label>
                        </div>
                    </fieldset>-->
                    
                    <fieldset>
                        <legend>Team Whitelists</legend>
                        
                        <div>One GUID per line. If a team is empty, anyone can join.</div>
                        
                        <div class="container">
                            <div class="row">
                                <div class="col-sm">
                                    <h5 class="text-center">Team 1</h5>
                                    <textarea class="w-100" name="whitelist-team-1">$whitelist_team_1</textarea>
                                </div>
                                <div class="col-sm">
                                    <h5 class="text-center">Team 2</h5>
                                    <textarea class="w-100" name="whitelist-team-2">$whitelist_team_2</textarea>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    
                    <br />
                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
EOF;

require '../page.php';
