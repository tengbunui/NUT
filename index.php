<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>衛理公會恩友堂點名系統</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            text-align: center;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 40px;
        }
        .menu-item {
            display: block;
            padding: 15px;
            margin: 10px auto;
            width: 80%;
            max-width: 400px;
            font-size: 18px;
            color: #ffffff;
            background-color: #007bff;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .menu-item:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>衛理公會恩友堂點名系統</h1>

        <a href="attendance.php" class="menu-item">我要點名</a>
        <a href="member_list.php" class="menu-item">新增會友名單與查詢</a>
        <a href="attendance_list.php" class="menu-item">出席記錄</a>
    </div>
</body>
</html>
