<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = '기본코드 관리';

$db = getDB();

// 현재 탭 (기본값: delivery_methods)
$currentTab = $_GET['tab'] ?? 'delivery_methods';

// 탭 목록
$tabs = [
    'delivery_methods' => '배송방법',
    'order_statuses' => '주문 구분',
    'pot_sizes' => '화분사이즈',
    'pot_types' => '화분종류',
    'pot_colors' => '화분색상',
    'plant_sizes' => '식물사이즈',
    'plant_types' => '식물종류',
    'ribbons' => '리본',
    'policies' => '받침',
    'accessories' => '부자재'
];

include __DIR__ . '/includes/header.php';
?>

<header class="flex items-center justify-between whitespace-nowrap bg-white/30 backdrop-blur-md border-b border-white/40 px-8 py-5 sticky top-0 z-20">
<div class="flex items-center gap-4">
<div>
<h2 class="text-slate-800 text-2xl font-bold leading-tight tracking-tight">기본코드 관리</h2>
<p class="text-slate-500 text-sm font-medium">발주하기 필드별 목록 관리</p>
</div>
</div>
</header>

<div class="flex-1 overflow-y-auto p-4 md:p-8">
<div class="max-w-7xl mx-auto">
<!-- 탭 메뉴 -->
<div class="glass-panel rounded-2xl p-4 mb-6">
<div class="flex flex-wrap gap-2">
<?php foreach ($tabs as $tabKey => $tabName): ?>
<button onclick="switchTab('<?php echo $tabKey; ?>')" 
    class="px-4 py-2 rounded-xl font-medium transition-all <?php echo $currentTab === $tabKey ? 'bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-sm' : 'bg-white/50 text-slate-700 hover:bg-white'; ?>">
<?php echo h($tabName); ?>
</button>
<?php endforeach; ?>
</div>
</div>

<!-- 목록 관리 영역 -->
<div class="glass-panel rounded-2xl p-6 md:p-8">
<div class="flex justify-between items-center mb-6">
<h3 class="text-slate-800 text-xl font-bold"><?php echo h($tabs[$currentTab] ?? '목록 관리'); ?></h3>
<button onclick="openAddModal()" class="px-4 py-2 bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all text-sm">
<span class="material-symbols-outlined align-middle text-sm">add</span> 추가
</button>
</div>

<div id="listContainer" class="space-y-2">
<!-- 목록이 여기에 동적으로 로드됩니다 -->
</div>
</div>
</div>
</div>

<!-- 추가/수정 모달 -->
<div id="itemModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
<div class="bg-white rounded-2xl p-6 w-full max-w-md">
<div class="flex justify-between items-center mb-4">
<h3 class="text-slate-800 text-lg font-bold" id="modalTitle">항목 추가</h3>
<button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
<span class="material-symbols-outlined">close</span>
</button>
</div>
<form id="itemForm" onsubmit="saveItem(event)">
<input type="hidden" id="itemId" name="id">
<div class="mb-4">
<label class="block text-sm font-medium text-slate-700 mb-2">이름</label>
<input type="text" id="itemName" name="name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div class="mb-4">
<label class="block text-sm font-medium text-slate-700 mb-2">표시 순서</label>
<input type="number" id="itemDisplayOrder" name="display_order" value="0" min="0" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div class="flex gap-3">
<button type="submit" class="flex-1 px-4 py-2.5 bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all">
저장
</button>
<button type="button" onclick="closeModal()" class="px-4 py-2.5 bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all">
취소
</button>
</div>
</form>
</div>
</div>

<script>
let currentTable = '<?php echo $currentTab; ?>';

// 페이지 로드 시 목록 불러오기
document.addEventListener('DOMContentLoaded', function() {
    loadList();
});

// 탭 전환
function switchTab(tab) {
    currentTable = tab;
    window.location.href = `?tab=${tab}`;
}

// 목록 불러오기
async function loadList() {
    try {
        const result = await apiCall(`/api/list_items.php?table=${currentTable}`, 'GET');
        
        if (result && result.data && result.data.items) {
            const container = document.getElementById('listContainer');
            container.innerHTML = '';
            
            if (result.data.items.length === 0) {
                container.innerHTML = '<div class="text-center py-12 text-slate-500"><p>등록된 항목이 없습니다.</p></div>';
                return;
            }
            
            result.data.items.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'flex items-center justify-between p-4 rounded-xl bg-white/50 border border-white/60 hover:shadow-md transition-all';
                itemDiv.innerHTML = `
                    <div class="flex-1">
                        <p class="text-slate-800 font-medium">${escapeHtml(item.name)}</p>
                        <p class="text-slate-500 text-xs mt-1">순서: ${item.display_order || 0}</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editItem(${item.id}, '${escapeHtml(item.name)}', ${item.display_order || 0})" 
                            class="px-3 py-1.5 bg-gradient-to-br from-blue-600 to-indigo-600 text-white text-xs rounded-lg hover:shadow-lg hover:scale-[1.02] transition-all font-medium">
                            수정
                        </button>
                        <button onclick="deleteItem(${item.id})" 
                            class="px-3 py-1.5 bg-gradient-to-br from-blue-600 to-indigo-600 text-white text-xs rounded-lg hover:shadow-lg hover:scale-[1.02] transition-all font-medium">
                            삭제
                        </button>
                    </div>
                `;
                container.appendChild(itemDiv);
            });
        }
    } catch (error) {
        console.error('Error loading list:', error);
        showNotification('목록을 불러오는 중 오류가 발생했습니다.', 'error');
    }
}

// 모달 열기 (추가)
function openAddModal() {
    document.getElementById('itemId').value = '';
    document.getElementById('itemName').value = '';
    document.getElementById('itemDisplayOrder').value = '0';
    document.getElementById('modalTitle').textContent = '항목 추가';
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
    document.getElementById('itemName').focus();
}

// 모달 열기 (수정)
function editItem(id, name, displayOrder) {
    document.getElementById('itemId').value = id;
    document.getElementById('itemName').value = name;
    document.getElementById('itemDisplayOrder').value = displayOrder || 0;
    document.getElementById('modalTitle').textContent = '항목 수정';
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
    document.getElementById('itemName').focus();
}

// 모달 닫기
function closeModal() {
    document.getElementById('itemModal').classList.add('hidden');
    document.getElementById('itemModal').classList.remove('flex');
    document.getElementById('itemForm').reset();
}

// 항목 저장
async function saveItem(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        id: formData.get('id') || null,
        name: formData.get('name'),
        display_order: parseInt(formData.get('display_order')) || 0
    };
    
    const method = data.id ? 'PUT' : 'POST';
    const url = `/api/list_items.php?table=${currentTable}`;
    
    try {
        const result = await apiCall(url, method, data);
        
        if (result) {
            showNotification(result.message || '저장되었습니다.', 'success');
            closeModal();
            loadList();
        }
    } catch (error) {
        console.error('Error saving item:', error);
        showNotification('저장 중 오류가 발생했습니다.', 'error');
    }
}

// 항목 삭제
async function deleteItem(id) {
    if (!confirm('이 항목을 삭제하시겠습니까?')) {
        return;
    }
    
    try {
        const result = await apiCall(`/api/list_items.php?table=${currentTable}&id=${id}`, 'DELETE');
        
        if (result) {
            showNotification(result.message || '삭제되었습니다.', 'success');
            loadList();
        }
    } catch (error) {
        console.error('Error deleting item:', error);
        showNotification('삭제 중 오류가 발생했습니다.', 'error');
    }
}

// HTML 이스케이프
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
