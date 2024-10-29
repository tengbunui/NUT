<?php
include 'config.php'; // 引入資料庫設定檔案

// 設定篩選的日期和出席項目，預設為今天
$selected_date = isset($_POST['attendance_date']) ? $_POST['attendance_date'] : date('Y-m-d');
$selected_category = isset($_POST['attendance_category']) ? $_POST['attendance_category'] : '全部';
$sort_option = isset($_POST['sort_option']) ? $_POST['sort_option'] : 'count'; // 預設排序選項
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

// 各堂次人數統計
$attendance_count_by_session_sql = "SELECT members.id, members.name, GROUP_CONCAT(categories.name SEPARATOR ',') AS categories
                                    FROM attendance
                                    JOIN members ON attendance.member_id = members.id
                                    JOIN member_categories ON members.id = member_categories.member_id
                                    JOIN categories ON member_categories.category_id = categories.id
                                    WHERE attendance.date = ?
                                    GROUP BY members.id";
$stmt = $conn->prepare($attendance_count_by_session_sql);
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$attendance_count_by_session_result = $stmt->get_result();

// 初始化各堂次人數
$session_counts = ['第一堂' => 0, '第二堂' => 0, '晚堂' => 0];

// 更新各堂次人數
while ($row = $attendance_count_by_session_result->fetch_assoc()) {
    foreach ($session_counts as $key => $value) {
        if (strpos($row['categories'], $key) !== false) {
            $session_counts[$key] += 1;
        }
    }
}
$stmt->close();

// 查詢特定日期的出席紀錄
$sql = "SELECT attendance.id, attendance.date, members.name AS member_name, attendance.category 
        FROM attendance
        JOIN members ON attendance.member_id = members.id
        WHERE attendance.date = '$selected_date'";

// 根據出席項目篩選
if ($selected_category != '全部') {
    $sql .= " AND attendance.category = '$selected_category'";
}

// 排序順序為 第一堂、第二堂、晚堂、其他
$sql .= " ORDER BY 
          CASE 
            WHEN attendance.category = '第一堂' THEN 1
            WHEN attendance.category = '第二堂' THEN 2
            WHEN attendance.category = '晚堂' THEN 3
            ELSE 4
          END";

$result = $conn->query($sql);

// 查詢所有會員的出席次數，並根據選擇的排序和日期範圍進行篩選
$attendance_count_sql = "SELECT members.name, 
                                SUM(CASE WHEN attendance.category = '主日崇拜' THEN 1 ELSE 0 END) AS sunday_count,
                                SUM(CASE WHEN attendance.category = '團契聚會' THEN 1 ELSE 0 END) AS fellowship_count,
                                SUM(CASE WHEN attendance.category = '小組聚會' THEN 1 ELSE 0 END) AS group_count,
                                COUNT(attendance.id) AS total_count
                         FROM members
                         LEFT JOIN attendance ON members.id = attendance.member_id";

// 加入日期範圍條件
$date_conditions = [];
if ($start_date != '') {
    $date_conditions[] = "attendance.date >= '$start_date'";
}
if ($end_date != '') {
    $date_conditions[] = "attendance.date <= '$end_date'";
}

if (!empty($date_conditions)) {
    $attendance_count_sql .= " WHERE " . implode(" AND ", $date_conditions);
}

$attendance_count_sql .= " GROUP BY members.id";

// 根據選擇的排序選項來排序
if ($sort_option == 'count') {
    $attendance_count_sql .= " ORDER BY total_count DESC";
} else {
    $attendance_count_sql .= " ORDER BY members.name ASC";
}

$attendance_count_result = $conn->query($attendance_count_sql);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>出席記錄列表與統計</title>
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
        h1, h2 {
            color: #333;
        }
        .filter-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        select, input[type="date"], input[type="text"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .attendance-count {
            font-size: 16px;
            font-weight: bold;
            margin-right: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
        }
        .session-1 { background-color: #e0f7fa; }
        .session-2 { background-color: #e8f5e9; }
        .session-3 { background-color: #fffde7; }
        .delete-button {
            background-color: #ff5252;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-to-home">回首頁</a> <!-- 回首頁按鈕 -->

        <h1>出席記錄列表與統計</h1>

        <!-- 日期、出席項目篩選和總人數顯示 -->
        <div class="filter-container">
            <span class="attendance-count">當天出席總人數：<?php echo array_sum($session_counts); ?></span>
            <span class="attendance-count">第一堂人數：<?php echo $session_counts['第一堂']; ?></span>
            <span class="attendance-count">第二堂人數：<?php echo $session_counts['第二堂']; ?></span>
            <span class="attendance-count">晚堂人數：<?php echo $session_counts['晚堂']; ?></span>

            <form method="post" action="">
                <label for="attendance_date">日期：</label>
                <input type="date" id="attendance_date" name="attendance_date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">

                <label for="attendance_category">出席項目：</label>
                <select id="attendance_category" name="attendance_category" onchange="this.form.submit()">
                    <option value="全部" <?php echo ($selected_category == '全部') ? 'selected' : ''; ?>>全部</option>
                    <option value="主日崇拜" <?php echo ($selected_category == '主日崇拜') ? 'selected' : ''; ?>>主日崇拜</option>
                    <option value="團契聚會" <?php echo ($selected_category == '團契聚會') ? 'selected' : ''; ?>>團契聚會</option>
                    <option value="小組聚會" <?php echo ($selected_category == '小組聚會') ? 'selected' : ''; ?>>小組聚會</option>
                </select>
            </form>
        </div>

        <!-- 出席記錄表格 -->
        <table>
            <tr>
                <th>日期</th>
                <th>會員名稱</th>
                <th>出席項目</th>
                <th>操作</th>
            </tr>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr id="row-<?php echo $row['id']; ?>" class="<?php echo ($row['category'] == '第一堂') ? 'session-1' : (($row['category'] == '第二堂') ? 'session-2' : (($row['category'] == '晚堂') ? 'session-3' : '')); ?>">
                        <td><?php echo $row['date']; ?></td>
                        <td><?php echo $row['member_name']; ?></td>
                        <td><?php echo $row['category']; ?></td>
                        <td><button class="delete-button" onclick="deleteRecord(<?php echo $row['id']; ?>)">刪除</button></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">目前沒有出席記錄</td>
                </tr>
            <?php endif; ?>
        </table>

        <!-- 出席次數統計 -->
        <h2>出席次數統計</h2>
        <form method="post" action="">
            <label for="sort_option">排序：</label>
            <select id="sort_option" name="sort_option" onchange="this.form.submit()">
                <option value="count" <?php echo ($sort_option == 'count') ? 'selected' : ''; ?>>依出席次數</option>
                <option value="name" <?php echo ($sort_option == 'name') ? 'selected' : ''; ?>>依會員名稱</option>
            </select>
            
            <label for="start_date">開始日期：</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">

            <label for="end_date">結束日期：</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            
            <button type="submit">更新統計</button>
        </form>
        
        <table>
            <tr>
                <th>會員名稱</th>
                <th>主日崇拜次數</th>
                <th>團契聚會次數</th>
                <th>小組聚會次數</th>
                <th>總出席次數</th>
            </tr>
            <?php if ($attendance_count_result && $attendance_count_result->num_rows > 0): ?>
                <?php while($row = $attendance_count_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['sunday_count']; ?></td>
                        <td><?php echo $row['fellowship_count']; ?></td>
                        <td><?php echo $row['group_count']; ?></td>
                        <td><?php echo $row['total_count']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">目前沒有統計資料</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <script>
        function deleteRecord(id) {
            if(confirm("確定要刪除此記錄嗎？")) {
                fetch('delete_record.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${id}`
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    if (data.includes("刪除成功")) {
                        document.getElementById(`row-${id}`).remove(); // 刪除成功後移除對應的行
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>
</body>
</html>
