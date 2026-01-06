<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = '템플릿 관리';

$db = getDB();

// 템플릿 목록
$stmt = $db->prepare("SELECT * FROM receipt_templates WHERE created_by = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['admin_id']]);
$templates = $stmt->fetchAll();

// 각 필드별 목록 조회 (order_create.php와 동일하게)
$listFields = [
    'delivery_methods' => 'delivery_method',
    'pot_sizes' => 'pot_size',
    'pot_types' => 'pot_type',
    'pot_colors' => 'pot_color',
    'plant_sizes' => 'plant_size',
    'plant_types' => 'plant_type',
    'ribbons' => 'ribbon',
    'policies' => 'policy',
    'accessories' => 'accessories'
];

$listData = [];
foreach ($listFields as $table => $field) {
    try {
        $stmt = $db->query("SELECT id, name FROM {$table} ORDER BY display_order ASC, name ASC");
        $listData[$field] = $stmt->fetchAll();
    } catch (PDOException $e) {
        $listData[$field] = [];
    }
}

include __DIR__ . '/includes/header.php';
?>

<header class="flex items-center justify-between whitespace-nowrap bg-white/30 backdrop-blur-md border-b border-white/40 px-8 py-5 sticky top-0 z-20">
<div class="flex items-center gap-4">
<div>
<h2 class="text-slate-800 text-2xl font-bold leading-tight tracking-tight">템플릿 관리</h2>
<p class="text-slate-500 text-sm font-medium">즐겨찾기 템플릿 생성 및 관리 (최대 10개)</p>
</div>
</div>
</header>

<div class="flex-1 overflow-y-auto py-4 md:py-8 pl-4 md:pl-8 pr-4 md:pr-8">
<div class="w-full max-w-7xl mx-auto px-4">
<!-- 템플릿 목록 -->
<div class="glass-panel rounded-2xl p-8 mb-6">
<div class="flex justify-between items-center mb-6">
<h3 class="text-slate-800 text-xl font-bold">등록된 템플릿 (<?php echo count($templates); ?>/10)</h3>
<button onclick="openTemplateModal()" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:shadow-lg transition-all">
<span class="material-symbols-outlined align-middle">add</span> 새 템플릿
</button>
</div>

<?php if (empty($templates)): ?>
<div class="text-center py-12 text-slate-500">
<p class="mb-2">등록된 템플릿이 없습니다.</p>
<p class="text-sm">템플릿을 생성하여 빠른 주문서 작성을 시작하세요.</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
<?php foreach ($templates as $template): ?>
<div class="bg-white/50 rounded-xl p-6 border border-white/60 hover:shadow-md transition-all">
<div class="flex justify-between items-start mb-4">
<h4 class="text-lg font-bold text-slate-800"><?php echo h($template['name']); ?></h4>
<div class="flex gap-2">
<button onclick="editTemplate(<?php echo $template['id']; ?>)" class="px-4 py-2 bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-lg hover:shadow-lg hover:scale-[1.02] transition-all text-sm font-medium">
수정
</button>
<button onclick="deleteTemplate(<?php echo $template['id']; ?>)" class="px-4 py-2 bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-lg hover:shadow-lg hover:scale-[1.02] transition-all text-sm font-medium">
삭제
</button>
</div>
</div>
<div class="space-y-2 text-sm text-slate-600">
<?php if ($template['delivery_method']): ?>
<p><span class="font-medium">배송방법:</span> <?php echo h($template['delivery_method']); ?></p>
<?php endif; ?>
<?php if ($template['plant_type']): ?>
<p><span class="font-medium">식물종류:</span> <?php echo h($template['plant_type']); ?></p>
<?php endif; ?>
<?php if ($template['pot_type']): ?>
<p><span class="font-medium">화분종류:</span> <?php echo h($template['pot_type']); ?></p>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>

<!-- 템플릿 생성/수정 모달 -->
<div id="templateModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
<div class="glass-panel rounded-2xl p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
<div class="flex justify-between items-center mb-6">
<h3 class="text-2xl font-bold text-slate-800" id="modalTitle">템플릿 생성</h3>
<button onclick="closeTemplateModal()" class="p-2 hover:bg-slate-100 rounded-lg">
<span class="material-symbols-outlined">close</span>
</button>
</div>

<form id="templateForm" class="space-y-6">
<input type="hidden" name="id" id="template_id">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">템플릿명칭 <span class="text-red-500">*</span></label>
<input type="text" name="name" id="template_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" placeholder="예: 인기 장미 바구니">
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">배송방법</label>
<select name="delivery_method" id="template_delivery_method" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['delivery_method'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">화분사이즈</label>
<select name="pot_size" id="template_pot_size" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['pot_size'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">화분종류</label>
<select name="pot_type" id="template_pot_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['pot_type'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">화분색상</label>
<select name="pot_color" id="template_pot_color" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['pot_color'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">식물사이즈</label>
<select name="plant_size" id="template_plant_size" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['plant_size'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">식물종류</label>
<select name="plant_type" id="template_plant_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['plant_type'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">리본</label>
<select name="ribbon" id="template_ribbon" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['ribbon'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">받침</label>
<select name="policy" id="template_policy" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['policy'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
</div>

<div>
<label class="block text-sm font-medium text-slate-700 mb-3">부자재 선택</label>
<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
<?php foreach ($listData['accessories'] ?? [] as $item): ?>
<label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer transition-all">
<input type="checkbox" name="accessories[]" value="<?php echo h($item['name']); ?>" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
<span class="text-sm text-slate-700"><?php echo h($item['name']); ?></span>
</label>
<?php endforeach; ?>
</div>
</div>

<div class="flex gap-4 pt-6 border-t border-slate-200">
<button type="submit" class="flex-1 py-3 bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all">
저장
</button>
<button type="button" onclick="closeTemplateModal()" class="px-6 py-3 bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all">
취소
</button>
</div>
</form>
</div>
</div>

<script>
let currentTemplateId = null;

function openTemplateModal(id = null) {
    currentTemplateId = id;
    document.getElementById('modalTitle').textContent = id ? '템플릿 수정' : '템플릿 생성';
    document.getElementById('templateForm').reset();
    document.getElementById('template_id').value = id || '';
    
    if (id) {
        loadTemplateData(id);
    }
    
    document.getElementById('templateModal').classList.remove('hidden');
    document.getElementById('templateModal').classList.add('flex');
}

function closeTemplateModal() {
    document.getElementById('templateModal').classList.add('hidden');
    document.getElementById('templateModal').classList.remove('flex');
    currentTemplateId = null;
}

async function loadTemplateData(id) {
    const result = await apiCall(`/api/templates.php`, 'GET');
    
    if (result && result.data) {
        const template = result.data.find(t => t.id == id);
        if (template) {
            document.getElementById('template_id').value = template.id;
            document.getElementById('template_name').value = template.name || '';
            document.getElementById('template_delivery_method').value = template.delivery_method || '';
            document.getElementById('template_pot_size').value = template.pot_size || '';
            document.getElementById('template_pot_type').value = template.pot_type || '';
            document.getElementById('template_pot_color').value = template.pot_color || '';
            document.getElementById('template_plant_size').value = template.plant_size || '';
            document.getElementById('template_plant_type').value = template.plant_type || '';
            document.getElementById('template_ribbon').value = template.ribbon || '';
            document.getElementById('template_policy').value = template.policy || '';
            
            if (template.accessories) {
                const accessories = template.accessories.split(',').map(a => a.trim());
                document.querySelectorAll('#templateModal input[name="accessories[]"]').forEach(checkbox => {
                    checkbox.checked = accessories.includes(checkbox.value);
                });
            }
        }
    }
}

async function editTemplate(id) {
    openTemplateModal(id);
}

async function deleteTemplate(id) {
    if (!confirm('이 템플릿을 삭제하시겠습니까?')) return;
    
    const result = await apiCall(`/api/templates.php?id=${id}`, 'DELETE');
    
    if (result) {
        showNotification('템플릿이 삭제되었습니다.', 'success');
        setTimeout(() => location.reload(), 1000);
    }
}

document.getElementById('templateForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (key === 'accessories[]') {
            if (!data.accessories) data.accessories = [];
            data.accessories.push(value);
        } else {
            data[key] = value;
        }
    }
    
    if (data.accessories) {
        data.accessories = data.accessories.join(', ');
    }
    
    const method = currentTemplateId ? 'PUT' : 'POST';
    const result = await apiCall('/api/templates.php', method, data);
    
    if (result) {
        showNotification(currentTemplateId ? '템플릿이 수정되었습니다.' : '템플릿이 생성되었습니다.', 'success');
        setTimeout(() => location.reload(), 1000);
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
