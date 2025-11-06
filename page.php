<?php
$search = $search ?? false;
$search_placeholder = $search_placeholder ?? '';
$page = $page ?? '';
$head = $head ?? '';
$body = $body ?? '';
$user = $user ?? null;

require_once 'db.php';
$db = new DB;
$server_name = htmlspecialchars($db->getSetting('title'));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?=$server_name?><?= (isset($title) ? " - $title" : '') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="logo-transparent.png" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --bg-dark: #121212;
        --bg-darker: #0a0a0a;
        --white-primary: #FFFFFF;
        --white-secondary: #E0E0E0;
        --white-accent: #F5F5F5;
        --text-light: #F5F5F5;
        --text-muted: #AAAAAA;
        --border-radius: 8px;
        --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Montserrat', sans-serif;
        background-color: var(--bg-dark);
        color: var(--text-light);
        line-height: 1.6;
        padding: 0;
        margin: 0;
        min-height: 100vh;
    }
    
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    h1 {
        color: var(--white-primary);
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 2.5rem;
        text-align: center;
        margin-bottom: 2rem;
        position: relative;
        padding-bottom: 1rem;
    }

    h1::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--white-primary), transparent);
    }
    
    .phase {
        background-color: var(--bg-darker);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        margin-bottom: 25px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .phase-header {
        background-color: rgba(255, 255, 255, 0.05);
        color: var(--white-secondary);
        padding: 15px 20px;
        font-weight: 600;
        font-size: 1.1em;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95em;
    }
    
    th {
        background-color: rgba(255, 255, 255, 0.1);
        padding: 12px 10px;
        text-align: left;
        color: var(--white-secondary);
        border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        font-weight: 600;
    }
    
    td {
        padding: 12px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    tr {
        background-color: rgba(255, 255, 255, 0.03);
        transition: var(--transition);
    }
    
    tr:hover {
        background-color: rgba(255, 255, 255, 0.08);
    }
    
    .qualified {
        background-color: rgba(224, 224, 224, 0.1);
        border-left: 3px solid var(--white-primary);
    }
    
    .team-name {
        cursor: pointer;
        color: var(--text-light);
        transition: color 0.2s;
        font-weight: 500;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .team-name:hover {
        color: var(--white-primary);
    }
    
    .stats {
        text-align: center;
    }
    
    .search-form {
        display: flex;
        gap: 10px;
        margin-left: auto;
    }
    
    .search-input {
        padding: 8px 12px;
        border: 1px solid var(--white-secondary);
        border-radius: var(--border-radius);
        background-color: var(--bg-darker);
        color: var(--text-light);
        font-family: 'Montserrat', sans-serif;
    }
    
    .search-button {
        padding: 8px 16px;
        background-color: var(--white-primary);
        color: var(--bg-dark);
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: var(--transition);
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
    }
    
    .search-button:hover {
        background-color: var(--white-accent);
        transform: translateY(-2px);
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .page-button {
        padding: 8px 12px;
        background-color: rgba(255, 255, 255, 0.1);
        color: var(--white-primary);
        border-radius: 4px;
        text-decoration: none;
        transition: var(--transition);
        border: 1px solid rgba(255, 255, 255, 0.2);
        font-family: 'Montserrat', sans-serif;
    }
    
    .page-button:hover, .page-button.active {
        background-color: var(--white-primary);
        color: var(--bg-dark);
        font-weight: bold;
    }
    
    .country-flag {
        width: 24px;
        height: 16px;
        border-radius: 2px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        object-fit: cover;
    }
    
    /* Rating colors - kept original as these are not gold accents */
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
    
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }
        
        .hide-mobile {
            display: none;
        }
        
        .phase-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .search-form {
            width: 100%;
            margin-left: 0;
        }
        
        h1 {
            font-size: 2rem;
        }
    }
</style>
    
    <?=$head?>
</head>
<body>
    <div class="container">
        <h1><?=$server_name?></h1>
        <?=$body?>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script>
        $(document).ready(function() {
            // Team name click handlers can go here
            $('.team-name').click(function() {
                // Your existing team detail toggle logic
            });
        });
    </script>
</body>
</html>