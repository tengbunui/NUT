<?php
include 'config.php'; // 引入資料庫設定檔案

// 確保使用 POST 請求，並且有傳遞正確的 ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $delete_id = $_POST['id'];

    // 使用 prepared statement 防止 SQL 注入
    $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();

    // 檢查刪除是否成功
    if ($stmt->affected_rows > 0) {
        echo "刪除成功！";
    } else {
        echo "刪除失敗，找不到記錄。";
    }

    $stmt->close();
} else {
    echo "無效的請求";
}
?>
