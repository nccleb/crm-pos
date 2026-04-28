<?php
session_start();

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// Store any session data if needed
if (isset($_GET['page'])) {
    $_SESSION["o"] = urldecode($_GET['page']);
}
if (isset($_GET['page1'])) {
    $_SESSION["p"] = urldecode($_GET['page1']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Record - Search</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="date"],
        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }

        input[type="date"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #f093fb;
            box-shadow: 0 0 0 3px rgba(240, 147, 251, 0.1);
        }

        input::placeholder {
            color: #aaa;
        }

        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 30px;
        }

        .btn-full {
            grid-column: 1 / -1;
        }

        button {
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(240, 147, 251, 0.4);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn-danger {
            background: #ff4757;
            color: white;
        }

        .btn-danger:hover {
            background: #ff3838;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 71, 87, 0.4);
        }

        @media (max-width: 480px) {
            .button-group {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login Record</h1>
        <form method="post" action="test477.php" id="searchForm">
            <div class="form-group">
                <label for="sta">Start Date</label>
                <input type="date" id="sta" name="sta" required>
            </div>
            
            <div class="form-group">
                <label for="end">End Date</label>
                <input type="date" id="end" name="end" required>
            </div>

            <div class="form-group">
                <label for="que">Queue Name</label>
                <input type="text" id="que" name="que" placeholder="Enter queue name" required>
            </div>

            <div class="form-group">
                <label for="age">Agent Name/ID</label>
                <input type="text" id="age" name="age" placeholder="Enter agent name or ID">
            </div>

            <div class="button-group">
                <button type="submit" class="btn-primary btn-full">Search</button>
                <button type="button" class="btn-secondary" onclick="location.reload()">Reload</button>
                <button type="button" class="btn-danger" onclick="quit()">Quit</button>
            </div>
        </form>
    </div>

    <script>
        function quit() {
            window.location.replace("test204.php?page=<?php echo $_SESSION["o"] ?? '' ?>&page1=<?php echo $_SESSION["p"] ?? '' ?>");   
        }

        // Set max date to today
       // const today = new Date().toISOString().split('T')[0];
       // document.getElementById('end').setAttribute('max', today);
        //document.getElementById('sta').setAttribute('max', today);
        
        // Set default dates (last 7 days)
       // const lastWeek = new Date();
       // lastWeek.setDate(lastWeek.getDate() - 7);
        //document.getElementById('sta').value = lastWeek.toISOString().split('T')[0];
        //document.getElementById('end').value = today;
    </script>
</body>
</html>