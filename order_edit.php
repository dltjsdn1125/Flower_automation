<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = '발주수정';

$db = getDB();

// 주문 목록 조회
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search) {
    // JOIN 제거로 인해 주문번호만 검색 (판매처/화원사는 별도 조회 후 필터링)
    $where[] = "order_number LIKE ?";
    $searchParam = "%$search%";
    $params[] = $searchParam;
}

if ($statusFilter) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// 전체 개수 (JOIN 제거: PostgREST는 JOIN을 직접 지원하지 않음)
$countSql = "SELECT COUNT(*) as total FROM orders $whereClause";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalCountResult = $countStmt->fetch();
$totalCount = $totalCountResult ? (int)$totalCountResult['total'] : 0;
$totalPages = ceil($totalCount / $limit);

// 주문 목록 (JOIN 제거: PostgREST는 JOIN을 직접 지원하지 않으므로 메인 테이블만 조회)
$sql = "SELECT id, order_number, order_date, status, order_amount, created_at,
    sales_channel_id, flower_shop_id, created_by
    FROM orders 
    $whereClause
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$orders = $stmt->fetchAll();

// 관련 데이터 조회 (별도 쿼리로 조회)
$salesChannelIds = array_unique(array_filter(array_column($orders, 'sales_channel_id')));
$flowerShopIds = array_unique(array_filter(array_column($orders, 'flower_shop_id')));
$adminIds = array_unique(array_filter(array_column($orders, 'created_by')));

$salesChannels = [];
if (!empty($salesChannelIds)) {
    $placeholders = implode(',', array_fill(0, count($salesChannelIds), '?'));
    $scStmt = $db->prepare("SELECT id, name FROM sales_channels WHERE id IN ($placeholders)");
    $scStmt->execute($salesChannelIds);
    $salesChannels = array_column($scStmt->fetchAll(), 'name', 'id');
}

$flowerShops = [];
if (!empty($flowerShopIds)) {
    $placeholders = implode(',', array_fill(0, count($flowerShopIds), '?'));
    $fsStmt = $db->prepare("SELECT id, name FROM flower_shops WHERE id IN ($placeholders)");
    $fsStmt->execute($flowerShopIds);
    $flowerShops = array_column($fsStmt->fetchAll(), 'name', 'id');
}

$admins = [];
if (!empty($adminIds)) {
    $placeholders = implode(',', array_fill(0, count($adminIds), '?'));
    $aStmt = $db->prepare("SELECT id, name FROM admins WHERE id IN ($placeholders)");
    $aStmt->execute($adminIds);
    $admins = array_column($aStmt->fetchAll(), 'name', 'id');
}

// 주문 목록에 관련 데이터 추가
foreach ($orders as &$order) {
    $order['sales_channel_name'] = $salesChannels[$order['sales_channel_id']] ?? '';
    $order['flower_shop_name'] = $flowerShops[$order['flower_shop_id']] ?? '';
    $order['created_by_name'] = $admins[$order['created_by']] ?? '';
}
unset($order);

// 상태 목록
$statusList = ['신규', '발주완료', '배송중', '배송완료', '취소'];

include __DIR__ . '/includes/header.php';
?>

<header class="flex items-center justify-between whitespace-nowrap bg-white/30 backdrop-blur-md border-b border-white/40 px-8 py-5 sticky top-0 z-20">
<div class="flex items-center gap-4">
<div>
<h2 class="text-slate-800 text-2xl font-bold leading-tight tracking-tight">발주수정</h2>
<p class="text-slate-500 text-sm font-medium">주문서 수정 및 관리</p>
</div>
</div>
</header>

<div class="flex-1 overflow-y-auto p-4 md:p-8">
<div class="max-w-7xl mx-auto">
<!-- 검색 및 필터 -->
<div class="glass-panel rounded-2xl p-6 mb-6">
<form method="GET" class="flex flex-col md:flex-row gap-4">
<div class="flex-1">
<input type="text" name="search" value="<?php echo h($search); ?>" 
    placeholder="주문번호, 판매처, 화원사 검색..." 
    class="w-full px-4 py-2 rounded-xl border border-white/60 bg-white/50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
</div>
<div class="md:w-48">
<select name="status" class="w-full px-4 py-2 rounded-xl border border-white/60 bg-white/50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
<option value="">전체 상태</option>
<?php foreach ($statusList as $status): ?>
<option value="<?php echo h($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
<?php echo h($status); ?>
</option>
<?php endforeach; ?>
</select>
</div>
<button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-colors">
검색
</button>
<a href="/order_edit.php" class="px-6 py-2 bg-slate-200 text-slate-700 rounded-xl font-bold hover:bg-slate-300 transition-colors text-center">
초기화
</a>
</form>
</div>

<!-- 일괄 삭제 도구 -->
<div class="glass-panel rounded-2xl p-4 mb-6" id="bulkActions" style="display: none;">
<div class="flex items-center justify-between flex-wrap gap-4">
<div class="flex items-center gap-4">
<span class="text-sm font-medium text-slate-700">
<span id="selectedCount">0</span>개 선택됨
</span>
<button onclick="deleteSelectedOrders()" class="px-4 py-2 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition-all text-sm">
일괄 삭제
</button>
<button onclick="clearSelection()" class="px-4 py-2 bg-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-300 transition-all text-sm">
선택 해제
</button>
</div>
</div>
</div>

<!-- 주문 목록 -->
<div class="glass-panel rounded-2xl overflow-hidden">
<div class="overflow-x-auto overscroll-x-contain">
<table class="w-full min-w-[800px]">
<thead class="bg-white/50 border-b border-white/60 sticky top-0 z-10">
<tr>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">
<input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">주문번호</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">주문일</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">판매처</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">화원사</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">상태</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">주문금액</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap hidden md:table-cell">작성자</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-slate-700 uppercase whitespace-nowrap">작업</th>
</tr>
</thead>
<tbody class="divide-y divide-white/30">
<?php if (empty($orders)): ?>
<tr>
<td colspan="9" class="px-6 py-12 text-center text-slate-500">
주문 내역이 없습니다.
</td>
</tr>
<?php else: ?>
<?php foreach ($orders as $order): ?>
<tr class="hover:bg-white/30 transition-colors">
<td class="px-4 md:px-6 py-3 md:py-4">
<input type="checkbox" class="order-checkbox w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" value="<?php echo $order['id']; ?>" onchange="updateSelection()">
</td>
<td class="px-4 md:px-6 py-3 md:py-4">
<span class="font-bold text-slate-800 text-sm"><?php echo h($order['order_number']); ?></span>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 text-slate-600 text-xs md:text-sm whitespace-nowrap">
<?php echo formatDate($order['order_date'], 'Y-m-d'); ?>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 text-slate-700 text-sm">
<?php echo h($order['sales_channel_name'] ?? '-'); ?>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 text-slate-700 text-sm">
<?php echo h($order['flower_shop_name'] ?? '-'); ?>
</td>
<td class="px-4 md:px-6 py-3 md:py-4">
<span class="px-2 md:px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-br from-blue-600 to-indigo-600 text-white whitespace-nowrap shadow-sm">
<?php echo h($order['status']); ?>
</span>
</td>
<td class="px-4 md:px-6 py-3 md:py-4">
<span class="font-bold text-slate-800 text-sm"><?php echo number_format($order['order_amount']); ?>원</span>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 text-slate-600 text-xs md:text-sm hidden md:table-cell">
<?php echo h($order['created_by_name'] ?? '-'); ?>
</td>
<td class="px-4 md:px-6 py-3 md:py-4">
<div class="flex gap-1 md:gap-2">
<button onclick="editOrder(<?php echo $order['id']; ?>)" 
    class="px-3 py-1 bg-gradient-to-br from-blue-600 to-indigo-600 text-white text-xs rounded-lg hover:shadow-lg hover:scale-[1.02] transition-all font-medium">
수정
</button>
<button onclick="deleteOrder(<?php echo $order['id']; ?>)" 
    class="px-3 py-1 bg-gradient-to-br from-blue-600 to-indigo-600 text-white text-xs rounded-lg hover:shadow-lg hover:scale-[1.02] transition-all font-medium">
삭제
</button>
</div>
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
전체 <?php echo number_format($totalCount); ?>건 (<?php echo $page; ?>/<?php echo $totalPages; ?>페이지)
</div>
<div class="flex gap-2">
<?php if ($page > 1): ?>
<a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" 
    class="px-4 py-2 bg-white/50 rounded-lg hover:bg-white transition-colors">
이전
</a>
<?php endif; ?>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
<a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" 
    class="px-4 py-2 <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-white/50 hover:bg-white'; ?> rounded-lg transition-colors">
<?php echo $i; ?>
</a>
<?php endfor; ?>
<?php if ($page < $totalPages): ?>
<a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>" 
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
function editOrder(id) {
    window.location.href = '/order_create.php?id=' + id;
}

function deleteOrder(id) {
    if (!confirm('정말 삭제하시겠습니까?')) return;
    
    fetch('/api/orders.php?id=' + id, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('삭제되었습니다.', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('오류: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('오류가 발생했습니다.', 'error');
        console.error(error);
    });
}

// 체크박스 선택 관리
function updateSelection() {
    const checkboxes = document.querySelectorAll('.order-checkbox:checked');
    const count = checkboxes.length;
    const selectedCount = document.getElementById('selectedCount');
    const bulkActions = document.getElementById('bulkActions');
    
    if (selectedCount) {
        selectedCount.textContent = count;
    }
    
    if (bulkActions) {
        bulkActions.style.display = count > 0 ? 'block' : 'none';
    }
    
    // 전체 선택 체크박스 상태 업데이트
    const selectAll = document.getElementById('selectAll');
    const allCheckboxes = document.querySelectorAll('.order-checkbox');
    if (selectAll && allCheckboxes.length > 0) {
        selectAll.checked = count === allCheckboxes.length;
        selectAll.indeterminate = count > 0 && count < allCheckboxes.length;
    }
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateSelection();
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }
    updateSelection();
}

// 선택된 주문 일괄 삭제
async function deleteSelectedOrders() {
    const checkboxes = document.querySelectorAll('.order-checkbox:checked');
    
    if (checkboxes.length === 0) {
        alert('선택된 주문이 없습니다.');
        return;
    }
    
    const orderIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (!confirm(`선택한 ${orderIds.length}개의 주문을 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.`)) {
        return;
    }
    
    // 재확인
    if (!confirm('정말로 삭제하시겠습니까?')) {
        return;
    }
    
    try {
        const result = await apiCall('/api/orders_bulk_delete.php', 'POST', {
            order_ids: orderIds
        });
        
        if (result) {
            showNotification(result.message || '주문이 삭제되었습니다.', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('삭제 중 오류가 발생했습니다.', 'error');
    }
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    updateSelection();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
