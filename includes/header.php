<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo isset($pageTitle) ? h($pageTitle) : '발주 관리 시스템'; ?></title>
    <!-- 폰트 로딩 최적화: preconnect만 사용, 실제 로딩은 지연 -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- 페이지 prefetch 제거: 과도한 prefetch로 인한 성능 저하 방지 -->
    <script id="tailwind-config">
        // Tailwind CSS가 로드된 후 실행되도록 설정
        window.tailwindConfig = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#3b82f6",
                        "glass-border": "rgba(255, 255, 255, 0.5)",
                        "glass-surface": "rgba(255, 255, 255, 0.4)",
                        "text-main": "#1e293b",
                        "text-muted": "#64748b",
                    },
                    fontFamily: {
                        "display": ["Manrope", "Noto Sans", "sans-serif"]
                    },
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "2xl": "1rem", "3xl": "1.5rem", "full": "9999px" },
                    boxShadow: {
                        'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.07)',
                    }
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        // Tailwind CSS 로드 후 config 적용
        if (window.tailwind && window.tailwindConfig) {
            tailwind.config = window.tailwindConfig;
        }
    </script>
    <style>
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1); 
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; 
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .animated-bg {
            background: linear-gradient(125deg, #f0f4ff 0%, #eef2ff 50%, #fdfbf7 100%);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
        }
        .glass-nav {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.6);
        }
    </style>
</head>
<body class="animated-bg text-text-main font-display overflow-hidden relative">
<div class="fixed top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-200/30 rounded-full blur-[100px] pointer-events-none z-0"></div>
<div class="fixed bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-200/30 rounded-full blur-[100px] pointer-events-none z-0"></div>
<div class="flex h-screen w-full relative z-10">
<!-- 모바일 오버레이 (사이드바 열릴 때 배경) -->
<div id="mobileOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 md:hidden hidden transition-opacity" onclick="toggleSidebar()"></div>
<!-- 사이드바 -->
<aside id="sidebar" class="fixed md:static inset-y-0 left-0 flex w-72 flex-col glass-nav z-50 md:z-30 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
<div class="flex h-full flex-col justify-between p-6">
<div class="flex flex-col gap-6">
<div class="flex items-center gap-3 px-2 py-2">
<div class="rounded-2xl size-10 bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
<span class="material-symbols-outlined">shopping_cart</span>
</div>
<div class="flex flex-col">
<h1 class="text-slate-800 text-xl font-bold leading-none tracking-tight">발주 관리</h1>
<p class="text-slate-500 text-xs font-medium mt-1">주문서 관리 시스템</p>
</div>
</div>
<nav class="flex flex-col gap-2">
<a class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-white/80 shadow-sm text-blue-700 border border-white/60' : 'hover:bg-white/50 text-slate-500 hover:text-slate-800'; ?> transition-all duration-200" href="/index.php">
<span class="material-symbols-outlined">dashboard</span>
<p class="text-sm font-bold">대시보드</p>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'order_create.php' ? 'bg-white/80 shadow-sm text-blue-700 border border-white/60' : 'hover:bg-white/50 text-slate-500 hover:text-slate-800'; ?> transition-all duration-200" href="/order_create.php">
<span class="material-symbols-outlined">shopping_cart</span>
<p class="text-sm font-medium">발주하기</p>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'order_edit.php' ? 'bg-white/80 shadow-sm text-blue-700 border border-white/60' : 'hover:bg-white/50 text-slate-500 hover:text-slate-800'; ?> transition-all duration-200" href="/order_edit.php">
<span class="material-symbols-outlined">edit</span>
<p class="text-sm font-medium">발주수정</p>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'customer.php' ? 'bg-white/80 shadow-sm text-blue-700 border border-white/60' : 'hover:bg-white/50 text-slate-500 hover:text-slate-800'; ?> transition-all duration-200" href="/customer.php">
<span class="material-symbols-outlined">person</span>
<p class="text-sm font-medium">고객정보</p>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'receipt_create.php' ? 'bg-white/80 shadow-sm text-blue-700 border border-white/60' : 'hover:bg-white/50 text-slate-500 hover:text-slate-800'; ?> transition-all duration-200" href="/receipt_create.php">
<span class="material-symbols-outlined">receipt_long</span>
<p class="text-sm font-medium">인수증 복수생성</p>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'receipt_list.php' ? 'bg-white/80 shadow-sm text-blue-700 border border-white/60' : 'hover:bg-white/50 text-slate-500 hover:text-slate-800'; ?> transition-all duration-200" href="/receipt_list.php">
<span class="material-symbols-outlined">list</span>
<p class="text-sm font-medium">인수증 목록</p>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'template.php' ? 'bg-white/80 shadow-sm text-blue-700 border border-white/60' : 'hover:bg-white/50 text-slate-500 hover:text-slate-800'; ?> transition-all duration-200" href="/template.php">
<span class="material-symbols-outlined">bookmark</span>
<p class="text-sm font-medium">템플릿 관리</p>
</a>
<a class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-white/80 shadow-sm text-blue-700 border border-white/60' : 'hover:bg-white/50 text-slate-500 hover:text-slate-800'; ?> transition-all duration-200" href="/settings.php">
<span class="material-symbols-outlined">settings</span>
<p class="text-sm font-medium">기본코드 관리</p>
</a>
</nav>
</div>
<div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/40 border border-white/50 mt-auto backdrop-blur-sm">
        <div class="bg-center bg-no-repeat bg-cover rounded-full size-10 shadow-sm border-2 border-white bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm">
            <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?>
</div>
<div class="flex flex-col flex-1">
<p class="text-slate-800 text-sm font-bold"><?php echo h($_SESSION['admin_name'] ?? '관리자'); ?></p>
<p class="text-slate-500 text-xs"><?php echo h($_SESSION['admin_email'] ?? 'admin@flower.com'); ?></p>
</div>
<a href="/logout.php" class="p-2 text-slate-600 hover:text-slate-800 hover:bg-white/50 rounded-lg transition-all" title="로그아웃">
<span class="material-symbols-outlined text-lg">logout</span>
</a>
</div>
</div>
</aside>
<main class="flex-1 flex flex-col h-full relative overflow-hidden">
<!-- 모바일 메뉴 버튼 -->
<button id="mobileMenuButton" onclick="toggleSidebar()" class="fixed top-4 left-4 z-50 md:hidden p-2 bg-white/80 backdrop-blur-md rounded-xl shadow-lg text-slate-800 hover:bg-white transition-all">
<span class="material-symbols-outlined">menu</span>
</button>
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    
    if (sidebar && overlay) {
        const isOpen = !sidebar.classList.contains('-translate-x-full');
        
        if (isOpen) {
            // 닫기
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        } else {
            // 열기
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }
    }
}

// 모바일에서 링크 클릭 시 사이드바 닫기
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('#sidebar nav a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 768) {
                toggleSidebar();
            }
        });
    });
    
    // 화면 크기 변경 시 데스크톱에서는 항상 사이드바 표시
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        
        if (window.innerWidth >= 768) {
            if (sidebar) sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
        }
    });
});
</script>