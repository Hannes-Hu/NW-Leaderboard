<?php
require '../db.php';
$db = new DB;

$title = 'Admin Login';

$user = $db->getUser();

if ($user !== null) {
    header("Location: /admin");
    exit;
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $loggedIn = false;
        
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $loggedIn = $db->logIn($_POST['username'], $_POST['password']);
        }

        if ($loggedIn) {
            header("Location: /admin");
        } else {
            header("Location: /admin?status=error");
        }
        exit;
    } else {
        $body = <<<'EOF'
<main class="container">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="my-3 p-3 bg-white rounded shadow">
                <form method="POST" action="login.php">
                    <fieldset>
                        <legend>Login</legend>
                        
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Username</span>
                            </div>
                            <input type="text" name="username" class="form-control" placeholder="Username" required autofocus />
                        </div>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Password</span>
                            </div>
                            <input type="password" name="password" class="form-control" placeholder="Password" required />
                        </div>
                        
                        <div class="form-group">
                            <button class="btn btn-primary mb-3" type="submit">Login</button>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</main>
EOF;
        require '../page.php';
    }
}
