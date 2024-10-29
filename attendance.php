<?php
session_start();
include 'config.php'; // 引入資料庫設定檔案

// 設定選擇的日期和聚會類別
$selected_date = $_POST['attendance_date'] ?? $_SESSION['attendance_date'] ?? date('Y-m-d');
$selected_category = $_POST['meeting_category'] ?? $_SESSION['meeting_category'] ?? '';

// 儲存選擇的日期和類別至會話中
$_SESSION['attendance_date'] = $selected_date;
$_SESSION['meeting_category'] = $selected_category;

// 處理出席紀錄插入
if (isset($_POST['mark_present'])) {
    $member_id = $_POST['member_id'];
    $stmt = $conn->prepare("INSERT INTO attendance (member_id, date, category) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $member_id, $selected_date, $selected_category);
    $stmt->execute();
    $stmt->close();
}

// 處理出席紀錄刪除
if (isset($_POST['remove_present'])) {
    $member_id = $_POST['member_id'];
    $stmt = $conn->prepare("DELETE FROM attendance WHERE member_id = ? AND date = ? AND category = ?");
    $stmt->bind_param("iss", $member_id, $selected_date, $selected_category);
    $stmt->execute();
    $stmt->close();
}

// 取得所有分類
$categories = $conn->query("SELECT * FROM categories");

// 取得當日和選定類別的出席會員
$present_members_sql = "SELECT attendance.member_id, members.name 
                        FROM attendance 
                        JOIN members ON attendance.member_id = members.id
                        WHERE attendance.date = ? AND attendance.category = ?";
$stmt = $conn->prepare($present_members_sql);
$stmt->bind_param("ss", $selected_date, $selected_category);
$stmt->execute();
$present_members_result = $stmt->get_result();

// 建立出席成員的清單
$present_members = [];
while ($row = $present_members_result->fetch_assoc()) {
    $present_members[$row['member_id']] = $row['name'];
}
$stmt->close();

// 取得分類下的會員名單
$filter_category_id = $_POST['filter_category'] ?? '';
$members_sql = "SELECT members.id, members.name FROM members";
if ($filter_category_id != '') {
    $members_sql .= " JOIN member_categories ON members.id = member_categories.member_id 
                      WHERE member_categories.category_id = ?";
    $stmt = $conn->prepare($members_sql);
    $stmt->bind_param("i", $filter_category_id);
    $stmt->execute();
    $members = $stmt->get_result();
} else {
    $members = $conn->query($members_sql);
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>點名系統</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        .container {
            width: 80%;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
        }

        .back-to-home {
            position: absolute;
            top: 20px;
            right: 20px;
            text-decoration: none;
            color: #ffffff;
            background-color: #007bff;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 14px;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .button-group .btn {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .button-group .btn.active {
            background-color: #ff6347;
            font-weight: bold;
            color: #ffffff;
            border: 2px solid #333;
        }

        .button-group .btn:hover {
            background-color: #0056b3;
        }

        .filter-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .members-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        .member-list {
            width: 45%;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .member-list h2 {
            color: #333;
        }

        .member-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
        }

        .member-item {
            padding: 10px;
            background-color: #007bff;
            color: #ffffff;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            transition: background-color 0.3s;
        }

        .member-item:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
        function setCategory(category) {
            document.getElementById('meeting_category').value = category;
            document.getElementById('category_form').submit();
        }

        function markAsPresent(memberId) {
            const form = document.getElementById('mark_present_form');
            form.member_id.value = memberId;
            form.submit();
        }

        function removeFromPresent(memberId) {
            const form = document.getElementById('remove_present_form');
            form.member_id.value = memberId;
            form.submit();
        }
    </script>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-to-home">回首頁</a>

        <h1>點名系統</h1>

        <div class="button-group">
            <button type="button" class="btn <?php echo ($selected_category == '主日崇拜') ? 'active' : ''; ?>" onclick="setCategory('主日崇拜')">主日崇拜</button>
            <button type="button" class="btn <?php echo ($selected_category == '團契聚會') ? 'active' : ''; ?>" onclick="setCategory('團契聚會')">團契聚會</button>
            <button type="button" class="btn <?php echo ($selected_category == '小組聚會') ? 'active' : ''; ?>" onclick="setCategory('小組聚會')">小組聚會</button>
        </div>
        
        <form method="post" id="category_form">
            <input type="hidden" name="meeting_category" id="meeting_category" value="<?php echo $selected_category; ?>">
        </form>

        <div class="filter-container">
            <form method="post" action="">
                <input type="date" name="attendance_date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
                <select name="filter_category" onchange="this.form.submit()">
                    <option value="">全部</option>
                    <?php while($category = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($filter_category_id == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <div class="members-container">
            <div class="member-list" id="attendance-list">
                <h2>點名</h2>
                <div class="member-grid">
                    <?php while($member = $members->fetch_assoc()): ?>
                        <?php if (!array_key_exists($member['id'], $present_members)): ?>
                            <div class="member-item" onclick="markAsPresent('<?php echo $member['id']; ?>')">
                                <?php echo $member['name']; ?>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="member-list" id="present-list">
                <h2>出席</h2>
                <div class="member-grid">
                    <?php foreach ($present_members as $present_member_id => $name): ?>
                        <div class="member-item" onclick="removeFromPresent('<?php echo $present_member_id; ?>')">
                            <?php echo $name; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <form id="mark_present_form" method="post" action="">
            <input type="hidden" name="mark_present" value="1">
            <input type="hidden" name="member_id" value="">
            <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
            <input type="hidden" name="meeting_category" value="<?php echo $selected_category; ?>">
        </form>
        <form id="remove_present_form" method="post" action="">
            <input type="hidden" name="remove_present" value="1">
            <input type="hidden" name="member_id" value="">
            <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
            <input type="hidden" name="meeting_category" value="<?php echo $selected_category; ?>">
        </form>
    </div>
</body>
</html>
