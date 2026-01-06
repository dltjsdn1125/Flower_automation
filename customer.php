<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = '고객정보';

$db = getDB();

// 고객 정보는 주문 데이터에서 추출
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';

// 주문자 정보 추출 (receipts 테이블에서, 중복 제거)
$sql = "SELECT DISTINCT 
    r.orderer_name as name,
    r.orderer_phone1 as phone1,
    r.orderer_phone2 as phone2,
    COUNT(r.id) as order_count,
    MAX(r.created_at) as last_order_date
    FROM receipts r
    WHERE r.orderer_name IS NOT NULL AND r.orderer_name != ''";
    
if ($search) {
    $sql .= " AND (r.orderer_name LIKE ? OR r.orderer_phone1 LIKE ? OR r.orderer_phone2 LIKE ?)";
}

$sql .= " GROUP BY r.orderer_name, r.orderer_phone1, r.orderer_phone2
    ORDER BY last_order_date DESC
    LIMIT ? OFFSET ?";

$stmt = $db->prepare($sql);
if ($search) {
    $searchParam = "%$search%";
    $stmt->execute([$searchParam, $searchParam, $searchParam, $limit, $offset]);
} else {
    $stmt->execute([$limit, $offset]);
}
$customers = $stmt->fetchAll();

// 전체 개수
$countSql = "SELECT COUNT(DISTINCT r.orderer_name) as total 
    FROM receipts r
    WHERE r.orderer_name IS NOT NULL AND r.orderer_name != ''";
if ($search) {
    $countSql .= " AND (r.orderer_name LIKE ? OR r.orderer_phone1 LIKE ? OR r.orderer_phone2 LIKE ?)";
}
$countStmt = $db->prepare($countSql);
if ($search) {
    $searchParam = "%$search%";
    $countStmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $countStmt->execute();
}
$totalCount = $countStmt->fetch()['total'];
$totalPages = ceil($totalCount / $limit);

include __DIR__ . '/includes/header.php';
?>

<header class="flex items-center justify-between whitespace-nowrap bg-white/30 backdrop-blur-md border-b border-white/40 px-8 py-5 sticky top-0 z-20">
<div class="flex items-center gap-4">
<div>
<h2 class="text-slate-800 text-2xl font-bold leading-tight tracking-tight">고객정보</h2>
<p class="text-slate-500 text-sm font-medium">고객 주문 이력 관리</p>
</div>
</div>
</header>

<div class="flex-1 overflow-y-auto p-4 md:p-8">
<div class="max-w-7xl mx-auto">
<!-- 검색 -->
<div class="glass-panel rounded-2xl p-6 mb-6">
<form method="GET" class="flex gap-4">
<div class="flex-1">
<input type="text" name="search" value="<?php echo h($search); ?>" 
    placeholder="고객명, 전화번호 검색..." 
    class="w-full px-4 py-2 rounded-xl border border-white/60 bg-white/50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
</div>
<button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-colors">
검색
</button>
<a href="/customer.php" class="px-6 py-2 bg-slate-200 text-slate-700 rounded-xl font-bold hover:bg-slate-300 transition-colors text-center">
초기화
</a>
</form>
</div>

<!-- 고객 목록 -->
<div class="glass-panel rounded-2xl overflow-hidden">
<div class="overflow-x-auto overscroll-x-contain">
<table class="w-full min-w-[600px]">
<thead class="bg-white/50 border-b border-white/60 sticky top-0 z-10">
<tr>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">고객명</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">연락처1</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap hidden md:table-cell">연락처2</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">주문건수</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">최근 주문일</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">작업</th>
</tr>
</thead>
<tbody class="divide-y divide-white/30">
<?php if (empty($customers)): ?>
<tr>
<td colspan="6" class="px-6 py-12 text-center text-slate-500">
고객 정보가 없습니다.
</td>
</tr>
<?php else: ?>
<?php foreach ($customers as $customer): ?>
<tr class="hover:bg-white/30 transition-colors">
<td class="px-4 md:px-6 py-3 md:py-4">
<span class="font-bold text-slate-800 text-sm"><?php echo h($customer['name']); ?></span>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 text-slate-600 text-xs md:text-sm">
<?php echo h($customer['phone1'] ?? '-'); ?>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 text-slate-600 text-xs md:text-sm hidden md:table-cell">
<?php echo h($customer['phone2'] ?? '-'); ?>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 text-center">
<span class="px-2 md:px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">
<?php echo number_format($customer['order_count']); ?>건
</span>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 text-slate-600 text-xs md:text-sm whitespace-nowrap">
<?php echo formatDate($customer['last_order_date'], 'Y-m-d'); ?>
</td>
<td class="px-4 md:px-6 py-3 md:py-4">
<button onclick="viewOrders('<?php echo h($customer['name']); ?>', '<?php echo h($customer['phone1'] ?? ''); ?>')" 
    class="px-2 md:px-3 py-1 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">
주문내역
</button>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- 페이지네이션 -->
<?php if ($totalPages > 1): ?>
<div class="px-6 py-4 border-t border-white/60 flex items-center justify-between">
<div class="text-sm text-slate-600">
전체 <?php echo number_format($totalCount); ?>명 (<?php echo $page; ?>/<?php echo $totalPages; ?>페이지)
</div>
<div class="flex gap-2">
<?php if ($page > 1): ?>
<a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
    class="px-4 py-2 bg-white/50 rounded-lg hover:bg-white transition-colors">
이전
</a>
<?php endif; ?>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
<a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
    class="px-4 py-2 <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-white/50 hover:bg-white'; ?> rounded-lg transition-colors">
<?php echo $i; ?>
</a>
<?php endfor; ?>
<?php if ($page < $totalPages): ?>
<a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
    class="px-4 py-2 bg-white/50 rounded-lg hover:bg-white transition-colors">
다음
</a>
<?php endif; ?>
</div>
</div>
<?php endif; ?>
</div>
</div>
</div>

<script>
function viewOrders(name, phone) {
    window.location.href = '/order_edit.php?search=' + encodeURIComponent(name);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
