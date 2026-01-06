<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = '발주 현황';

// 통계 데이터 조회 (최적화: 단일 쿼리로 통합)
$db = getDB();

// 데이터베이스 타입 확인 (캐싱으로 성능 개선)
static $isSqlite = null;
if ($isSqlite === null) {
    $driverName = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driverName === 'sqlite');
}

// 통계 데이터를 한 번에 조회 (성능 최적화)
if ($isSqlite) {
    // SQLite: 통계 쿼리 최적화
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT 
        COUNT(*) as daily_orders,
        SUM(CASE WHEN status IN ('발주완료', '배송완료') THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = '신규' THEN 1 ELSE 0 END) as pending_orders
        FROM orders WHERE date(order_date) = ?");
    $stmt->execute([$today]);
    $stats = $stmt->fetch();
    $dailyOrders = $stats['daily_orders'] ?? 0;
    $completedOrders = $stats['completed_orders'] ?? 0;
    $pendingOrders = $stats['pending_orders'] ?? 0;
    
    // 배송 완료율 (7일)
    $stmt = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = '배송완료' THEN 1 ELSE 0 END) as completed
        FROM orders 
        WHERE date(order_date) >= date('now', '-7 days')");
    $deliveryStats = $stmt->fetch();
    $deliveryRate = $deliveryStats['total'] > 0 ? round(($deliveryStats['completed'] / $deliveryStats['total']) * 100, 1) : 0;
    
    // 일일 주문 처리량
    $stmt = $db->query("SELECT 
        CAST(strftime('%H', created_at) AS INTEGER) as hour,
        COUNT(*) as count
        FROM orders 
        WHERE date(created_at) = date('now')
        GROUP BY strftime('%H', created_at)
        ORDER BY hour");
    $hourlyData = $stmt->fetchAll();
} else {
    // MySQL: 통계 쿼리 최적화
    $stmt = $db->query("SELECT 
        COUNT(*) as daily_orders,
        SUM(CASE WHEN status IN ('발주완료', '배송완료') THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = '신규' THEN 1 ELSE 0 END) as pending_orders
        FROM orders WHERE DATE(order_date) = CURDATE()");
    $stats = $stmt->fetch();
    $dailyOrders = $stats['daily_orders'] ?? 0;
    $completedOrders = $stats['completed_orders'] ?? 0;
    $pendingOrders = $stats['pending_orders'] ?? 0;
    
    // 배송 완료율 (7일)
    $stmt = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = '배송완료' THEN 1 ELSE 0 END) as completed
        FROM orders 
        WHERE DATE(order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $deliveryStats = $stmt->fetch();
    $deliveryRate = $deliveryStats['total'] > 0 ? round(($deliveryStats['completed'] / $deliveryStats['total']) * 100, 1) : 0;
    
    // 일일 주문 처리량
    $stmt = $db->query("SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as count
        FROM orders 
        WHERE DATE(created_at) = CURDATE()
        GROUP BY HOUR(created_at)
        ORDER BY hour");
    $hourlyData = $stmt->fetchAll();
}

// 전일 대비 증가율 계산 (일일 주문 건수)
$yesterday = date('Y-m-d', strtotime('-1 day'));
if ($isSqlite) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE date(order_date) = ?");
    $stmt->execute([$yesterday]);
} else {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = ?");
    $stmt->execute([$yesterday]);
}
$yesterdayOrders = $stmt->fetch()['count'] ?? 0;
$orderTrend = 0;
if ($yesterdayOrders > 0) {
    $orderTrend = round((($dailyOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1);
} elseif ($dailyOrders > 0) {
    $orderTrend = 100; // 전일 0건이면 100% 증가
}

// 발주 완료율 계산
$completionRate = $dailyOrders > 0 ? round(($completedOrders / $dailyOrders) * 100, 1) : 0;

// 최근 주문 내역 (인덱스 활용, 필요한 컬럼만 선택)
$stmt = $db->prepare("SELECT o.id, o.order_number, o.plant_type, o.pot_type, o.created_at,
    sc.name as sales_channel_name, fs.name as flower_shop_name 
    FROM orders o 
    LEFT JOIN sales_channels sc ON o.sales_channel_id = sc.id 
    LEFT JOIN flower_shops fs ON o.flower_shop_id = fs.id 
    ORDER BY o.created_at DESC 
    LIMIT 5");
$stmt->execute();
$recentOrders = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<header class="flex items-center justify-between whitespace-nowrap bg-white/30 backdrop-blur-md border-b border-white/40 px-8 py-5 sticky top-0 z-20">
<div class="flex items-center gap-4">
<button class="md:hidden text-slate-800">
<span class="material-symbols-outlined">menu</span>
</button>
<div>
<h2 class="text-slate-800 text-2xl font-bold leading-tight tracking-tight">발주 현황</h2>
<p class="text-slate-500 text-sm font-medium">주문서 및 발주 현황 모니터링</p>
</div>
</div>
<div class="flex items-center gap-4">
</div>
</header>

<div class="flex-1 overflow-y-auto p-4 md:p-8 space-y-6">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
<div class="glass-panel rounded-2xl p-6 hover:shadow-lg transition-shadow duration-300 group">
<div class="flex justify-between items-start">
<div class="rounded-2xl size-10 bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
<span class="material-symbols-outlined">receipt_long</span>
</div>
<?php if ($orderTrend != 0): ?>
<div class="flex items-center gap-1 <?php echo $orderTrend > 0 ? 'bg-green-50 border-green-100' : 'bg-red-50 border-red-100'; ?> px-2 py-1 rounded-full border">
<span class="material-symbols-outlined text-xs <?php echo $orderTrend > 0 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo $orderTrend > 0 ? 'trending_up' : 'trending_down'; ?></span>
<span class="text-xs font-bold <?php echo $orderTrend > 0 ? 'text-green-700' : 'text-red-700'; ?>"><?php echo $orderTrend > 0 ? '+' : ''; ?><?php echo $orderTrend; ?>%</span>
</div>
<?php endif; ?>
</div>
<div class="mt-4">
<p class="text-slate-500 text-sm font-medium">일일 주문 건수</p>
<p class="text-slate-800 text-3xl font-extrabold tracking-tight mt-1"><?php echo number_format($dailyOrders); ?></p>
</div>
</div>

<div class="glass-panel rounded-2xl p-6 hover:shadow-lg transition-shadow duration-300 group">
<div class="flex justify-between items-start">
<div class="rounded-2xl size-10 bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
<span class="material-symbols-outlined">task_alt</span>
</div>
<?php if ($completionRate > 0): ?>
<div class="flex items-center gap-1 bg-slate-50 px-2 py-1 rounded-full border border-slate-100">
<span class="text-slate-600 text-xs font-medium">성공률 <?php echo $completionRate; ?>%</span>
</div>
<?php endif; ?>
</div>
<div class="mt-4">
<p class="text-slate-500 text-sm font-medium">발주 완료</p>
<p class="text-slate-800 text-3xl font-extrabold tracking-tight mt-1"><?php echo number_format($completedOrders); ?></p>
</div>
</div>

<div class="glass-panel rounded-2xl p-6 hover:shadow-lg transition-shadow duration-300 group">
<div class="flex justify-between items-start">
<div class="rounded-2xl size-10 bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
<span class="material-symbols-outlined">local_shipping</span>
</div>
<?php if ($deliveryRate >= 80): ?>
<div class="flex items-center gap-1 bg-black px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-white text-xs">check_circle</span>
<span class="text-white text-xs font-bold">정상</span>
</div>
<?php elseif ($deliveryRate >= 50): ?>
<div class="flex items-center gap-1 bg-black px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-white text-xs">warning</span>
<span class="text-white text-xs font-bold">주의</span>
</div>
<?php else: ?>
<div class="flex items-center gap-1 bg-black px-2 py-1 rounded-full">
<span class="material-symbols-outlined text-white text-xs">error</span>
<span class="text-white text-xs font-bold">위험</span>
</div>
<?php endif; ?>
</div>
<div class="mt-4">
<p class="text-slate-500 text-sm font-medium">배송 완료율</p>
<p class="text-slate-800 text-3xl font-extrabold tracking-tight mt-1"><?php echo $deliveryRate; ?>%</p>
</div>
</div>

<div class="glass-panel rounded-2xl p-6 hover:shadow-lg transition-shadow duration-300 group relative overflow-hidden">
<div class="absolute -right-4 -top-4 p-3 opacity-5">
<span class="material-symbols-outlined text-blue-600 text-9xl">schedule</span>
</div>
<div class="flex justify-between items-start relative z-10">
<div class="rounded-2xl size-10 bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
<span class="material-symbols-outlined">schedule</span>
</div>
<div class="flex items-center gap-1 bg-black px-2 py-1 rounded-full">
<span class="text-white text-xs font-bold">긴급</span>
</div>
</div>
<div class="mt-4 relative z-10">
<p class="text-slate-500 text-sm font-medium">대기 중 주문</p>
<p class="text-slate-800 text-3xl font-extrabold tracking-tight mt-1"><?php echo number_format($pendingOrders); ?></p>
</div>
</div>
</div>

<div class="w-full glass-panel rounded-2xl p-8">
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
<div>
<h3 class="text-slate-800 text-lg font-bold leading-tight">일일 주문 처리량</h3>
<p class="text-slate-500 text-sm mt-1">지난 24시간 동안의 주문 및 발주 처리 흐름</p>
</div>
<div class="flex items-center gap-1 bg-slate-100/50 p-1 rounded-xl">
<button class="px-4 py-1.5 text-xs font-bold text-blue-700 bg-white rounded-lg shadow-sm">24시간</button>
<button class="px-4 py-1.5 text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">7일</button>
<button class="px-4 py-1.5 text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">30일</button>
</div>
</div>
<div class="relative w-full h-[320px]">
<?php
// 시간대별 데이터를 24시간 배열로 변환
$hourlyArray = array_fill(0, 24, 0);
foreach ($hourlyData as $row) {
    $hour = (int)$row['hour'];
    if ($hour >= 0 && $hour < 24) {
        $hourlyArray[$hour] = (int)$row['count'];
    }
}

// 최대값 계산 (차트 스케일링용)
$maxValue = max($hourlyArray);
$maxValue = $maxValue > 0 ? $maxValue : 1; // 0으로 나누기 방지

// SVG 경로 생성
$points = [];
$pathData = '';
$areaPath = '';
$chartHeight = 250; // 차트 높이 (300 - 50 여백)
$chartWidth = 1000;

for ($i = 0; $i < 24; $i++) {
    $x = ($i / 23) * $chartWidth;
    $y = $chartHeight - (($hourlyArray[$i] / $maxValue) * $chartHeight);
    $points[] = ['x' => $x, 'y' => $y, 'value' => $hourlyArray[$i]];
    
    if ($i === 0) {
        $pathData = "M0 {$y}";
        $areaPath = "M0 {$y}";
    } else {
        $prevX = (($i - 1) / 23) * $chartWidth;
        $prevY = $chartHeight - (($hourlyArray[$i - 1] / $maxValue) * $chartHeight);
        $midX = ($prevX + $x) / 2;
        $pathData .= " C{$prevX} {$prevY}, {$midX} {$y}, {$x} {$y}";
        $areaPath .= " C{$prevX} {$prevY}, {$midX} {$y}, {$x} {$y}";
    }
}
$areaPath .= " V {$chartHeight} H 0 Z";
?>
<svg class="w-full h-full overflow-visible" fill="none" preserveAspectRatio="none" viewBox="0 0 1000 300" xmlns="http://www.w3.org/2000/svg">
<!-- 그리드 라인 -->
<line stroke="#cbd5e1" stroke-dasharray="4 4" x1="0" x2="1000" y1="299" y2="299"></line>
<line stroke="#cbd5e1" stroke-dasharray="4 4" x1="0" x2="1000" y1="225" y2="225"></line>
<line stroke="#cbd5e1" stroke-dasharray="4 4" x1="0" x2="1000" y1="150" y2="150"></line>
<line stroke="#cbd5e1" stroke-dasharray="4 4" x1="0" x2="1000" y1="75" y2="75"></line>
<defs>
<linearGradient id="chartGradient" x1="0" x2="0" y1="0" y2="1">
<stop offset="0%" stop-color="#3b82f6" stop-opacity="0.2"></stop>
<stop offset="100%" stop-color="#3b82f6" stop-opacity="0"></stop>
</linearGradient>
</defs>
<!-- 영역 채우기 -->
<path d="<?php echo $areaPath; ?>" fill="url(#chartGradient)"></path>
<!-- 라인 -->
<path d="<?php echo $pathData; ?>" fill="none" stroke="#000000" stroke-width="3"></path>
<!-- 데이터 포인트 -->
<?php foreach ($points as $point): ?>
<?php if ($point['value'] > 0): ?>
<circle cx="<?php echo $point['x']; ?>" cy="<?php echo $point['y']; ?>" fill="white" r="4" stroke="#000000" stroke-width="2"></circle>
<?php endif; ?>
<?php endforeach; ?>
</svg>
</div>
<div class="flex justify-between text-slate-400 text-xs font-semibold mt-4 px-2 tracking-wide">
<span>00:00</span>
<span>04:00</span>
<span>08:00</span>
<span>12:00</span>
<span>16:00</span>
<span>20:00</span>
<span>23:59</span>
</div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 glass-panel rounded-2xl flex flex-col">
<div class="p-6 border-b border-slate-100 flex justify-between items-center">
<h3 class="text-slate-800 text-lg font-bold">최근 주문 내역</h3>
<a href="/order_edit.php" class="text-blue-600 text-sm font-bold hover:text-blue-700 hover:underline">전체 보기</a>
</div>
<div class="p-4 flex flex-col gap-2">
<?php if (empty($recentOrders)): ?>
<div class="text-center py-12 text-slate-500">
<p class="mb-2">주문 내역이 없습니다.</p>
<p class="text-sm">새로운 주문을 생성해보세요.</p>
</div>
<?php else: ?>
<?php foreach ($recentOrders as $order): ?>
<div class="flex items-center gap-4 p-4 rounded-xl hover:bg-white/50 transition-colors cursor-default border border-transparent hover:border-white/50">
<div class="size-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
<span class="material-symbols-outlined text-xl">article</span>
</div>
<div class="flex-1 min-w-0">
<p class="text-slate-800 text-sm font-bold truncate"><?php echo h($order['order_number']); ?> - <?php echo h($order['sales_channel_name'] ?? '-'); ?></p>
<p class="text-slate-500 text-xs truncate mt-0.5"><?php echo h($order['plant_type'] ?? '-'); ?> / <?php echo h($order['pot_type'] ?? '-'); ?></p>
</div>
<span class="text-slate-400 text-xs whitespace-nowrap font-medium"><?php echo formatDate($order['created_at'], 'm/d H:i'); ?></span>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>

<div class="glass-panel rounded-2xl p-6 flex flex-col gap-6">
<h3 class="text-slate-800 text-lg font-bold">발주 상태</h3>
<div class="flex flex-col gap-5">
<div class="flex justify-between items-center pb-5 border-b border-slate-100/50 last:border-0 last:pb-0">
<div class="flex items-center gap-4">
<span class="relative flex h-3 w-3">
<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
<span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
</span>
<div class="flex flex-col">
<p class="text-slate-800 text-sm font-bold">주문 처리 시스템</p>
<p class="text-slate-500 text-xs mt-0.5">정상 가동 중</p>
</div>
</div>
<span class="text-white font-bold text-xs bg-black px-2.5 py-1 rounded-md">ONLINE</span>
</div>
<div class="flex justify-between items-center pb-5 border-b border-slate-100/50 last:border-0 last:pb-0">
<div class="flex items-center gap-4">
<span class="relative flex h-3 w-3">
<span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
<span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
</span>
<div class="flex flex-col">
<p class="text-slate-800 text-sm font-bold">배송 관리 시스템</p>
<p class="text-slate-500 text-xs mt-0.5">처리율: <?php echo $deliveryRate; ?>%</p>
</div>
</div>
<span class="text-white font-bold text-xs bg-black px-2.5 py-1 rounded-md">ONLINE</span>
</div>
<div class="flex justify-between items-center pb-5 border-b border-slate-100/50 last:border-0 last:pb-0">
<div class="flex items-center gap-4">
<span class="relative flex h-3 w-3">
<span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
</span>
<div class="flex flex-col">
<p class="text-slate-800 text-sm font-bold">인수증 생성 시스템</p>
<?php
$stmt = $db->query("SELECT COUNT(*) as count FROM receipt_templates");
$templateCount = $stmt->fetch()['count'];
?>
<p class="text-slate-500 text-xs mt-0.5">즐겨찾기 템플릿: <?php echo $templateCount; ?>개</p>
</div>
</div>
<span class="text-white font-bold text-xs bg-black px-2.5 py-1 rounded-md">WARNING</span>
</div>
</div>
<button class="mt-auto w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg shadow-blue-500/30 text-white text-sm font-bold hover:shadow-xl hover:scale-[1.02] transition-all">
전체 현황 조회
</button>
</div>
</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
