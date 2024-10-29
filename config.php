<?php
// 設定資料庫連線的參數
$servername = "localhost";
$username = "root";         // XAMPP 預設使用者為 "root"
$password = "";             // XAMPP 預設密碼為空
$dbname = "church_attendance"; // 使用你在 phpMyAdmin 中建立的資料庫名稱

// 建立資料庫連接
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連接是否成功
if ($conn->connect_error) {
    die("連接失敗：" . $conn->connect_error);
} else {
    echo "成功連接到資料庫！";
}
?>
