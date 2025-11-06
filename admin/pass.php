<?php
require '../db.php';
$db = new DB;
$user = $db->getUserOrRedirect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['password'] ?? null;
    $pass2 = $_POST['password2'] ?? null;
    
    if ($pass1 !== null && $pass1 === $pass2) {
        $db->changePassword($user['user_id'], $pass1);
        header("Location: /admin");
    } else {
        header("Location: /admin/pass.php?status=error");
    }
    exit;
} else {
    $body = <<<EOF
    <main class="container">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="my-3 p-3 bg-white rounded shadow">
                    <form method="POST">
                        <fieldset>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">New Password</span>
                                </div>
                                <input type="password" name="password" class="form-control" placeholder="Password" required />
                            </div>
                            
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Repeat New Password</span>
                                </div>
                                <input type="password" name="password2" class="form-control" placeholder="Password" required />
                            </div>
                        </fieldset>
                        
                        <div class="form-group">
                            <button class="btn btn-primary" type="submit">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
EOF;
    $page = 'user-pass';
    $title = 'Change Password';
    require '../page.php';
}
