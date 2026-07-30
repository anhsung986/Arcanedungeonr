<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Vui lòng điền đủ thông tin!"]);
        exit;
    }

    // Thông tin kết nối lấy từ cấu hình AuthMe của bạn
    $host = '127.0.0.1';
    $port = 3306;
    $db   = 'authme';
    $user = 'authme';
    $pass = '12345';

    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        echo json_encode(["status" => "error", "message" => "Lỗi kết nối Database!"]);
        exit;
    }

    // Kiểm tra tên tài khoản đã tồn tại chưa (dựa theo cột username)
    $stmt = $conn->prepare("SELECT id FROM authme WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Tên nhân vật này đã tồn tại trong game!"]);
        exit;
    }
    $stmt->close();

    // Mã hóa mật khẩu chuẩn BCrypt cho AuthMe
    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $current_time = round(microtime(true) * 1000); // AuthMe thường lưu thời gian dạng timestamp mili-giây

    // Thêm dữ liệu vào đúng các cột: username, realname, password, ip, regip, regdate
    $insert = $conn->prepare("INSERT INTO authme (username, realname, password, ip, regip, regdate, isLogged, hasSession) VALUES (?, ?, ?, ?, ?, ?, 0, 0)");
    $insert->bind_param("sssssi", $username, $username, $hashed_password, $client_ip, $client_ip, $current_time);

    if ($insert->execute()) {
        echo json_encode(["status" => "success", "message" => "Đăng ký thành công! Vào game dùng lệnh /login để chơi."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Lỗi hệ thống khi ghi dữ liệu!"]);
    }

    $insert->close();
    $conn->close();
}
?>
  
