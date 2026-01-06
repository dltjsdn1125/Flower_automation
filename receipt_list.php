<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = '인수증 목록';

$db = getDB();

// 페이지네이션
$page = intval($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// 검색 조건
$search = $_GET['search'] ?? '';
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(receipt_number LIKE ? OR orderer_name LIKE ? OR recipient_name LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// 인수증 목록 조회
$sql = "SELECT * FROM receipts $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$receipts = $stmt->fetchAll();

// 전체 개수
$countSql = "SELECT COUNT(*) as total FROM receipts $whereClause";
$countStmt = $db->prepare($countSql);
$countParams = array_slice($params, 0, -2);
if (empty($countParams)) {
    $countStmt->execute();
} else {
    $countStmt->execute($countParams);
}
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / $limit);

include __DIR__ . '/includes/header.php';
?>

<header class="flex items-center justify-between whitespace-nowrap bg-white/30 backdrop-blur-md border-b border-white/40 px-8 py-5 sticky top-0 z-20">
<div class="flex items-center gap-4">
<div>
<h2 class="text-slate-800 text-2xl font-bold leading-tight tracking-tight">인수증 목록</h2>
<p class="text-slate-500 text-sm font-medium">생성된 인수증 조회 및 관리</p>
</div>
</div>
<div class="flex items-center gap-4">
<a href="/receipt_create.php" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:shadow-lg transition-all">
<span class="material-symbols-outlined align-middle">add</span> 새로 만들기
</a>
</div>
</header>

<div class="flex-1 overflow-y-auto p-4 md:p-8">
<div class="max-w-7xl mx-auto">
<!-- 검색 -->
<div class="glass-panel rounded-2xl p-6 mb-6">
<form method="GET" class="flex gap-4">
<div class="flex-1">
<input type="text" name="search" value="<?php echo h($search); ?>" placeholder="인수증번호, 주문자명, 수취인명으로 검색..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all">
<span class="material-symbols-outlined align-middle">search</span> 검색
</button>
</form>
</div>

<!-- 상태 변경 도구 -->
<div class="glass-panel rounded-2xl p-4 mb-6" id="bulkActions" style="display: none;">
<div class="flex items-center justify-between flex-wrap gap-4">
<div class="flex items-center gap-4">
<span class="text-sm font-medium text-slate-700">
<span id="selectedCount">0</span>개 선택됨
</span>
<select id="statusSelect" class="px-4 py-2 rounded-xl border border-slate-200 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
<option value="">상태 선택</option>
<option value="대기">대기</option>
<option value="배송중">배송중</option>
<option value="배송완료">배송완료</option>
<option value="취소">취소</option>
</select>
<button onclick="updateSelectedStatus()" class="px-4 py-2 bg-black text-white font-bold rounded-xl hover:bg-slate-800 transition-all text-sm">
상태 변경
</button>
<button onclick="deleteSelectedReceipts()" class="px-4 py-2 bg-black text-white font-bold rounded-xl hover:bg-slate-800 transition-all text-sm">
삭제
</button>
<button onclick="clearSelection()" class="px-4 py-2 bg-black text-white font-bold rounded-xl hover:bg-slate-800 transition-all text-sm">
선택 해제
</button>
</div>
</div>
</div>

<!-- 인수증 목록 -->
<div class="glass-panel rounded-2xl overflow-hidden">
<div class="overflow-x-auto overscroll-x-contain">
<table class="w-full text-left border-collapse min-w-[800px]">
<thead>
<tr class="bg-slate-50/50 text-slate-500 text-xs font-bold uppercase tracking-wider border-b border-slate-200 sticky top-0 z-10">
<th class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">
<input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
</th>
<th class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">인수증번호</th>
<th class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">상태</th>
<th class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">주문자</th>
<th class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap hidden md:table-cell">수취인</th>
<th class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">배달일시</th>
<th class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap hidden lg:table-cell">배달주소</th>
<th class="px-4 md:px-6 py-3 md:py-4 whitespace-nowrap">생성일</th>
<th class="px-4 md:px-6 py-3 md:py-4 text-right whitespace-nowrap">작업</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-100">
<?php if (empty($receipts)): ?>
<tr>
<td colspan="9" class="px-6 py-12 text-center text-slate-500">
인수증이 없습니다.
</td>
</tr>
<?php else: ?>
<?php foreach ($receipts as $receipt): ?>
<tr class="hover:bg-white/40 transition-colors">
<td class="px-4 md:px-6 py-3 md:py-4">
<input type="checkbox" class="receipt-checkbox w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" value="<?php echo $receipt['id']; ?>" onchange="updateSelection()">
</td>
<td class="px-4 md:px-6 py-3 md:py-4">
<span class="font-bold text-slate-800 text-sm"><?php echo h($receipt['receipt_number']); ?></span>
</td>
<td class="px-4 md:px-6 py-3 md:py-4">
<?php
$status = $receipt['status'] ?? '대기';
$statusColors = [
    '대기' => 'bg-blue-100 text-blue-700',
    '배송중' => 'bg-yellow-100 text-yellow-700',
    '배송완료' => 'bg-green-100 text-green-700',
    '취소' => 'bg-red-100 text-red-700'
];
$color = $statusColors[$status] ?? 'bg-slate-100 text-slate-700';
?>
<span class="px-2 md:px-3 py-1 rounded-full text-xs font-bold <?php echo $color; ?> whitespace-nowrap">
<?php echo h($status); ?>
</span>
</td>
<td class="px-4 md:px-6 py-3 md:py-4">
<div class="text-xs md:text-sm">
<div class="font-medium text-slate-800"><?php echo h($receipt['orderer_name'] ?? '-'); ?></div>
<?php if ($receipt['orderer_phone1']): ?>
<div class="text-slate-500 text-xs"><?php echo h($receipt['orderer_phone1']); ?></div>
<?php endif; ?>
</div>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 hidden md:table-cell">
<div class="text-sm">
<div class="font-medium text-slate-800"><?php echo h($receipt['recipient_name'] ?? '-'); ?></div>
<?php if ($receipt['recipient_phone1']): ?>
<div class="text-slate-500 text-xs"><?php echo h($receipt['recipient_phone1']); ?></div>
<?php endif; ?>
</div>
</td>
<td class="px-4 md:px-6 py-3 md:py-4">
<div class="text-xs md:text-sm">
<?php if ($receipt['delivery_date']): ?>
<div class="font-medium text-slate-800 whitespace-nowrap"><?php echo formatDate($receipt['delivery_date']); ?></div>
<?php if ($receipt['delivery_detail_time']): ?>
<div class="text-slate-500 text-xs"><?php echo h($receipt['delivery_detail_time']); ?></div>
<?php endif; ?>
<?php else: ?>
<span class="text-slate-400">-</span>
<?php endif; ?>
</div>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 hidden lg:table-cell">
<div class="text-xs md:text-sm text-slate-600 max-w-xs truncate">
<?php echo h($receipt['delivery_address'] ?? '-'); ?>
</div>
</td>
<td class="px-4 md:px-6 py-3 md:py-4">
<span class="text-xs text-slate-500 whitespace-nowrap"><?php echo formatDate($receipt['created_at'], 'Y-m-d'); ?></span>
</td>
<td class="px-4 md:px-6 py-3 md:py-4 text-right">
<button onclick="viewReceipt(<?php echo $receipt['id']; ?>)" class="px-2 md:px-3 py-1 md:py-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-all text-xs md:text-sm font-medium whitespace-nowrap">
보기
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
<div class="p-4 border-t border-slate-200 flex items-center justify-between">
<div class="text-sm text-slate-500">
총 <?php echo number_format($total); ?>건 중 <?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $limit, $total)); ?>건 표시
</div>
<div class="flex gap-2">
<?php if ($page > 1): ?>
<a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-all text-sm font-medium">이전</a>
<?php endif; ?>
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
<a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1.5 <?php echo $i == $page ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200'; ?> rounded-lg hover:bg-blue-50 transition-all text-sm font-medium">
<?php echo $i; ?>
</a>
<?php endfor; ?>
<?php if ($page < $totalPages): ?>
<a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-all text-sm font-medium">다음</a>
<?php endif; ?>
</div>
</div>
<?php endif; ?>
</div>
</div>
</div>
</div>

<script>
function viewReceipt(id) {
    window.location.href = `/receipt_view.php?id=${id}`;
}

// 체크박스 선택 관리
function updateSelection() {
    const checkboxes = document.querySelectorAll('.receipt-checkbox:checked');
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
    const allCheckboxes = document.querySelectorAll('.receipt-checkbox');
    if (selectAll && allCheckboxes.length > 0) {
        selectAll.checked = count === allCheckboxes.length;
        selectAll.indeterminate = count > 0 && count < allCheckboxes.length;
    }
}

function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.receipt-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateSelection();
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.receipt-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }
    updateSelection();
}

// 선택된 인수증의 상태 변경
async function updateSelectedStatus() {
    const checkboxes = document.querySelectorAll('.receipt-checkbox:checked');
    const statusSelect = document.getElementById('statusSelect');
    
    if (checkboxes.length === 0) {
        alert('선택된 인수증이 없습니다.');
        return;
    }
    
    const status = statusSelect.value;
    if (!status) {
        alert('변경할 상태를 선택해주세요.');
        return;
    }
    
    const receiptIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (!confirm(`${receiptIds.length}개의 인수증 상태를 "${status}"로 변경하시겠습니까?`)) {
        return;
    }
    
    try {
        const result = await apiCall('/api/receipts_bulk_status.php', 'POST', {
            receipt_ids: receiptIds,
            status: status
        });
        
        if (result) {
            showNotification(result.message || '상태가 변경되었습니다.', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('상태 변경 중 오류가 발생했습니다.', 'error');
    }
}

// 선택된 인수증 삭제
async function deleteSelectedReceipts() {
    const checkboxes = document.querySelectorAll('.receipt-checkbox:checked');
    
    if (checkboxes.length === 0) {
        alert('선택된 인수증이 없습니다.');
        return;
    }
    
    const receiptIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (!confirm(`선택한 ${receiptIds.length}개의 인수증을 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.`)) {
        return;
    }
    
    // 재확인
    if (!confirm('정말로 삭제하시겠습니까?')) {
        return;
    }
    
    try {
        const result = await apiCall('/api/receipts_bulk_delete.php', 'POST', {
            receipt_ids: receiptIds
        });
        
        if (result) {
            showNotification(result.message || '인수증이 삭제되었습니다.', 'success');
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
