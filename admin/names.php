<?php
require '../db.php';
$db = new DB;
$user = $db->getUserOrRedirect();

$body = '
<div class="container-fluid container-no-padding">
    <table class="table table-striped">
        <thead class="thead-dark">
            <tr>
                <th scope="col">GUID</th>
                <th scope="col">Name</th>
                <th scope="col">Last Used</th>
            </tr>
        </thead>
        <tbody>
';

if (isset($_GET['search'])) {
    $names = $db->getPlayerAliases($_GET['search']);
    
    foreach($names as $name) {
        $body .= "
            <tr>
                <td>{$name['guid']}</td>
                <td>{$name['name']}</td>
                <td>{$name['last_used']}</td>
            </tr>";
    }
}

$body .= '
        </tbody>
    </table>
</div>
';

$page = 'admin-names';
$title = 'Name History';
$search = true;
$search_placeholder = 'Exact Name or GUID';
require '../page.php';
