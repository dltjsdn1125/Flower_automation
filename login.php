<?php
require_once __DIR__ . '/config/config.php';

// 이미 로그인된 경우 리다이렉트
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '사용자명과 비밀번호를 입력해주세요.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                header('Location: /index.php');
                exit;
            } else {
                $error = '사용자명 또는 비밀번호가 올바르지 않습니다.';
            }
        } catch (Exception $e) {
            // 데이터베이스 연결 실패 시 임시 처리
            $error = '데이터베이스 연결에 실패했습니다. 데이터베이스를 먼저 설정해주세요.';
            error_log("Login Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>로그인 - 발주 관리 시스템</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(125deg, #f0f4ff 0%, #eef2ff 50%, #fdfbf7 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md">
<div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-xl p-8">
<div class="text-center mb-8">
<div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl text-white shadow-lg mb-4">
<span class="material-symbols-outlined text-3xl">shopping_cart</span>
</div>
<h1 class="text-2xl font-bold text-slate-800 mb-2">발주 관리 시스템</h1>
<p class="text-slate-500 text-sm">관리자 로그인</p>
</div>

<?php if ($error): ?>
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
<?php echo h($error); ?>
</div>
<?php endif; ?>

<form method="POST" class="space-y-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">사용자명</label>
<input type="text" name="username" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="사용자명을 입력하세요">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">비밀번호</label>
<input type="password" name="password" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="비밀번호를 입력하세요">
</div>
<button type="submit" class="w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all">
로그인
</button>
</form>
</div>
</div>
</body>
</html>
