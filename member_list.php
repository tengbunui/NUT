<?php
include 'config.php'; // 引入資料庫設定檔案

// 處理新增會員
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_member_name'])) {
    $name = $_POST['new_member_name'];
    $categories = $_POST['categories'] ?? [];

    // 插入會員資料
    $stmt = $conn->prepare("INSERT INTO members (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $member_id = $stmt->insert_id;
    $stmt->close();

    // 插入會員分類資料
    if ($member_id && !empty($categories)) {
        $stmt = $conn->prepare("INSERT INTO member_categories (member_id, category_id) VALUES (?, ?)");
        foreach ($categories as $category_id) {
            $stmt->bind_param("ii", $member_id, $category_id);
            $stmt->execute();
        }
        $stmt->close();
    }

    echo "<p>會員新增成功！</p>";
}

// 處理刪除會員
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // 刪除會員和分類資料
    $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();

    $stmt = $conn->prepare("DELETE FROM member_categories WHERE member_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    echo "<p>會員刪除成功！</p>";
}

// 處理更新會員分類（AJAX 請求）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update_member_id'])) {
    $member_id = $_POST['ajax_update_member_id'];
    $categories = $_POST['categories'] ?? [];

    // 刪除該會員的所有分類
    $stmt = $conn->prepare("DELETE FROM member_categories WHERE member_id = ?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();

    // 插入新的分類
    if (!empty($categories)) {
        $stmt = $conn->prepare("INSERT INTO member_categories (member_id, category_id) VALUES (?, ?)");
        foreach ($categories as $category_id) {
            $stmt->bind_param("ii", $member_id, $category_id);
            $stmt->execute();
        }
        $stmt->close();
    }

    echo "會員分類更新成功！";
    exit;
}

// 設置分類篩選
$filter_category_id = $_POST['filter_category'] ?? '';

// 取得會員名單，按姓氏筆劃排序
$member_sql = "SELECT members.id, members.name, GROUP_CONCAT(categories.name SEPARATOR ', ') AS categories
               FROM members
               LEFT JOIN member_categories ON members.id = member_categories.member_id
               LEFT JOIN categories ON member_categories.category_id = categories.id";
if ($filter_category_id) {
    $member_sql .= " WHERE members.id IN (SELECT member_id FROM member_categories WHERE category_id = ?)";
}
$member_sql .= " GROUP BY members.id ORDER BY CHAR_LENGTH(members.name) ASC, members.name ASC";

$stmt = $conn->prepare($member_sql);
if ($filter_category_id) {
    $stmt->bind_param("i", $filter_category_id);
}
$stmt->execute();
$members = $stmt->get_result();

// 取得所有分類
$category_result = $conn->query("SELECT * FROM categories");
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>會員名單</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 {
            color: #333;
            margin: 0;
        }
        .back-home {
            padding: 10px;
            color: #ffffff;
            background-color: #007bff;
            border-radius: 4px;
            text-decoration: none;
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
        .edit-button, .delete-button {
            color: #ffffff;
            background-color: #007bff;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
        }
        .new-member-form {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        #editForm {
            display: <?php echo isset($_GET['edit_id']) ? 'block' : 'none'; ?>;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>會員名單</h1>
            <a href="index.php" class="back-home">回首頁</a>
        </div>

        <!-- 新增會員表單 -->
        <div class="new-member-form">
            <h2>新增會員</h2>
            <form method="POST" action="">
                <label for="new_member_name">姓名：</label>
                <input type="text" id="new_member_name" name="new_member_name" required>

                <label for="categories">分類：</label>
                <div class="category-grid">
                    <?php $category_result->data_seek(0); ?>
                    <?php while($category = $category_result->fetch_assoc()): ?>
                        <label>
                            <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>">
                            <?php echo $category['name']; ?>
                        </label>
                    <?php endwhile; ?>
                </div>

                <button type="submit">新增會員</button>
            </form>
        </div>

        <!-- 篩選分類 -->
        <div class="filter-container">
            <form method="POST" action="">
                <label for="filter_category">選擇分類以篩選會員：</label>
                <select id="filter_category" name="filter_category" onchange="this.form.submit()">
                    <option value="">全部</option>
                    <?php
                    $category_result->data_seek(0); // 重置分類資料指標
                    while($category = $category_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo ($filter_category_id == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <table>
            <tr>
                <th>姓名</th>
                <th>分類</th>
                <th>操作</th>
            </tr>
            <?php while ($member = $members->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $member['name']; ?></td>
                    <td><?php echo $member['categories']; ?></td>
                    <td>
                        <a href="?edit_id=<?php echo $member['id']; ?>" class="edit-button" onclick="showEditForm(<?php echo $member['id']; ?>)">修改</a>
                        <a href="?delete_id=<?php echo $member['id']; ?>" class="delete-button">刪除</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>

        <!-- 編輯會員分類的表單 -->
        <?php if (isset($_GET['edit_id'])):
            $edit_id = $_GET['edit_id'];
            $member_sql = "SELECT * FROM members WHERE id = ?";
            $stmt = $conn->prepare($member_sql);
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $member = $stmt->get_result()->fetch_assoc();

            // 取得該會員的分類
            $member_categories_sql = "SELECT category_id FROM member_categories WHERE member_id = ?";
            $stmt = $conn->prepare($member_categories_sql);
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $member_categories_result = $stmt->get_result();
            $member_categories = [];
            while ($row = $member_categories_result->fetch_assoc()) {
                $member_categories[] = $row['category_id'];
            }
            $stmt->close();
        ?>
            <div id="editForm">
                <h2>編輯會員分類 - <?php echo $member['name']; ?></h2>
                <form id="editFormInner" method="POST" onsubmit="updateMember(event, <?php echo $edit_id; ?>)">
                    <div class="category-grid">
                        <?php $category_result->data_seek(0); ?>
                        <?php while ($category = $category_result->fetch_assoc()): ?>
                            <label>
                                <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>"
                                    <?php echo in_array($category['id'], $member_categories) ? 'checked' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </label>
                        <?php endwhile; ?>
                    </div>
                    <input type="hidden" name="ajax_update_member_id" value="<?php echo $edit_id; ?>">
                    <button type="submit">更新</button>
                </form>
            </div>
        <?php endif; ?>

        <script>
            function showEditForm(id) {
                document.getElementById('editForm').style.display = 'block';
            }

            function updateMember(event, id) {
                event.preventDefault();
                const form = document.getElementById('editFormInner');
                const formData = new FormData(form);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    alert(data);
                    document.getElementById('editForm').style.display = 'none';
                    window.location.reload();
                })
                .catch(error => console.error('Error:', error));
            }
        </script>
    </div>
</body>
</html>
