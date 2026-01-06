<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = '발주하기';

$db = getDB();

// 판매처 목록 (필요한 컬럼만 선택)
$stmt = $db->query("SELECT id, name FROM sales_channels ORDER BY name");
$salesChannels = $stmt->fetchAll();

// 화원사 목록 (필요한 컬럼만 선택)
$stmt = $db->query("SELECT id, name FROM flower_shops ORDER BY name");
$flowerShops = $stmt->fetchAll();

// 템플릿 목록 (최대 10개, 필요한 컬럼만 선택)
$stmt = $db->prepare("SELECT id, name, delivery_method, plant_type, pot_type FROM receipt_templates WHERE created_by = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['admin_id']]);
$templates = $stmt->fetchAll();

// 각 필드별 목록 조회
$listFields = [
    'delivery_methods' => 'delivery_method',
    'order_statuses' => 'status',
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
<h2 class="text-slate-800 text-2xl font-bold leading-tight tracking-tight">발주하기</h2>
<p class="text-slate-500 text-sm font-medium">새로운 주문서 작성</p>
</div>
</div>
</header>

<div class="flex-1 overflow-y-auto p-4 md:p-8">
<div class="max-w-7xl mx-auto">
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
<!-- 좌측: 주문서 작성 폼 -->
<div class="lg:col-span-3">
<div class="glass-panel rounded-2xl p-6 md:p-8">
<form id="orderForm" class="space-y-4 md:space-y-6">
<!-- 기본 정보 -->
<div class="border-b border-slate-200 pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">기본 정보</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">주문일 <span class="text-red-500">*</span></label>
<input type="date" name="order_date" id="order_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">판매처 <span class="text-red-500">*</span></label>
<select name="sales_channel_id" id="sales_channel_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($salesChannels as $channel): ?>
<option value="<?php echo $channel['id']; ?>"><?php echo h($channel['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">화원사 <span class="text-red-500">*</span></label>
<select name="flower_shop_id" id="flower_shop_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($flowerShops as $shop): ?>
<option value="<?php echo $shop['id']; ?>"><?php echo h($shop['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">발주담당자 <span class="text-red-500">*</span></label>
<input type="text" name="manager_name" id="manager_name" required value="<?php echo h($_SESSION['admin_name'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
</div>
</div>

<!-- 배송 정보 -->
<div class="border-b border-slate-200 pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">배송 정보</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">배송방법 <span class="text-red-500">*</span></label>
<select name="delivery_method" id="delivery_method" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['delivery_method'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">주문구분</label>
<select name="status" id="status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['status'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
</div>

<!-- 화분 정보 -->
<div class="border-b border-slate-200 pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">화분 정보</h3>
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">화분사이즈</label>
<select name="pot_size" id="pot_size" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['pot_size'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">화분종류</label>
<select name="pot_type" id="pot_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['pot_type'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">화분색상</label>
<select name="pot_color" id="pot_color" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['pot_color'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
</div>

<!-- 식물 정보 -->
<div class="border-b border-slate-200 pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">식물 정보</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">식물사이즈</label>
<select name="plant_size" id="plant_size" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['plant_size'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">식물종류</label>
<select name="plant_type" id="plant_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['plant_type'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
</div>

<!-- 부자재 정보 -->
<div class="border-b border-slate-200 pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">부자재 정보</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">리본</label>
<select name="ribbon" id="ribbon" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['ribbon'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">받침</label>
<select name="policy" id="policy" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<?php foreach ($listData['policy'] ?? [] as $item): ?>
<option value="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
<div class="mt-4">
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
</div>

<!-- 금액 정보 -->
<div class="pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">금액 정보</h3>
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">배송비</label>
<input type="number" name="delivery_fee" id="delivery_fee" value="0" min="0" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">판매금액 <span class="text-red-500">*</span></label>
<input type="number" name="sales_amount" id="sales_amount" required min="0" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">주문금액 <span class="text-red-500">*</span></label>
<input type="number" name="order_amount" id="order_amount" required min="0" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
</div>
</div>

<!-- 버튼 -->
<div class="flex gap-4 pt-6 border-t border-slate-200">
<button type="submit" class="flex-1 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all">
<span class="material-symbols-outlined align-middle">save</span> 저장
</button>
<button type="button" onclick="resetForm()" class="px-6 py-3 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-all">
초기화
</button>
<button type="button" onclick="history.back()" class="px-6 py-3 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-all">
취소
</button>
</div>
</form>
</div>
</div>

<!-- 우측: 템플릿 즐겨찾기 -->
<div class="lg:col-span-1">
<div class="glass-panel rounded-2xl p-6">
<h3 class="text-slate-800 text-lg font-bold mb-4">즐겨찾기 템플릿</h3>
<div class="space-y-2 max-h-[500px] overflow-y-auto overscroll-contain">
<?php if (empty($templates)): ?>
<p class="text-slate-500 text-sm text-center py-8">등록된 템플릿이 없습니다.<br><a href="/template.php" class="text-blue-600 hover:underline">템플릿 생성하기</a></p>
<?php else: ?>
<?php foreach ($templates as $template): ?>
<button onclick="loadTemplate(<?php echo $template['id']; ?>)" class="w-full px-4 py-3 rounded-xl bg-white/50 hover:bg-white border border-white/60 text-left transition-all hover:shadow-md group">
<div class="flex items-center justify-between">
<span class="text-sm font-medium text-slate-800 group-hover:text-blue-600"><?php echo h($template['name']); ?></span>
<span class="material-symbols-outlined text-slate-400 text-lg group-hover:text-blue-600">play_arrow</span>
</div>
</button>
<?php endforeach; ?>
<?php endif; ?>
</div>
<button onclick="window.location.href='/template.php'" class="mt-4 w-full py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-bold hover:shadow-lg transition-all">
<span class="material-symbols-outlined text-lg align-middle">add</span> 템플릿 관리
</button>
</div>
</div>
</div>
</div>
</div>

<script>
// 템플릿으로 채워진 필드 목록 (주문구분 제외)
const templateFields = ['delivery_method', 'pot_size', 'pot_type', 'pot_color', 'plant_size', 'plant_type', 'ribbon', 'policy'];

// 필드에 템플릿 스타일 적용
function markAsTemplateField(elementId, hasValue) {
    const el = document.getElementById(elementId);
    if (!el) return;

    // 기존 클래스 제거 후 새 스타일 적용
    el.classList.remove('bg-blue-50', 'border-blue-300', 'border-slate-200');

    if (hasValue) {
        el.classList.add('bg-blue-50', 'border-blue-300');
    } else {
        el.classList.add('border-slate-200');
    }
}

// 모든 템플릿 필드 스타일 초기화
function resetTemplateFieldStyles() {
    templateFields.forEach(field => markAsTemplateField(field, false));
    // 부자재 체크박스 스타일 초기화
    document.querySelectorAll('input[name="accessories[]"]').forEach(checkbox => {
        const label = checkbox.closest('label');
        if (label) {
            label.classList.remove('bg-blue-50', 'border-blue-300');
        }
    });
}

// 템플릿 로드
async function loadTemplate(templateId) {
    const result = await apiCall(`/api/templates.php?id=${templateId}`, 'GET');

    if (result && result.data) {
        const template = result.data;

        // 모든 템플릿 필드 스타일 초기화
        resetTemplateFieldStyles();

        // 템플릿 데이터로 폼 채우기 + 스타일 적용
        // select 요소의 옵션을 정확히 매칭하기 위해 값 비교
        function setSelectValue(selectId, value) {
            const select = document.getElementById(selectId);
            if (!select || !value) return false;
            
            // 정확한 값 매칭 시도
            for (let option of select.options) {
                if (option.value === value || option.text === value) {
                    select.value = option.value;
                    return true;
                }
            }
            // 부분 매칭 시도 (대소문자 무시)
            for (let option of select.options) {
                if (option.value.toLowerCase() === value.toLowerCase() || 
                    option.text.toLowerCase() === value.toLowerCase()) {
                    select.value = option.value;
                    return true;
                }
            }
            return false;
        }

        if (template.delivery_method) {
            if (setSelectValue('delivery_method', template.delivery_method)) {
                markAsTemplateField('delivery_method', true);
            }
        }
        if (template.pot_size) {
            if (setSelectValue('pot_size', template.pot_size)) {
                markAsTemplateField('pot_size', true);
            }
        }
        if (template.pot_type) {
            if (setSelectValue('pot_type', template.pot_type)) {
                markAsTemplateField('pot_type', true);
            }
        }
        if (template.pot_color) {
            if (setSelectValue('pot_color', template.pot_color)) {
                markAsTemplateField('pot_color', true);
            }
        }
        if (template.plant_size) {
            if (setSelectValue('plant_size', template.plant_size)) {
                markAsTemplateField('plant_size', true);
            }
        }
        if (template.plant_type) {
            if (setSelectValue('plant_type', template.plant_type)) {
                markAsTemplateField('plant_type', true);
            }
        }
        if (template.ribbon) {
            if (setSelectValue('ribbon', template.ribbon)) {
                markAsTemplateField('ribbon', true);
            }
        }
        if (template.policy) {
            if (setSelectValue('policy', template.policy)) {
                markAsTemplateField('policy', true);
            }
        }

        // 부자재 체크박스 처리
        if (template.accessories) {
            const accessories = template.accessories.split(',').map(a => a.trim());
            document.querySelectorAll('input[name="accessories[]"]').forEach(checkbox => {
                checkbox.checked = accessories.includes(checkbox.value);
                // 체크된 부자재의 부모 라벨에 스타일 적용
                const label = checkbox.closest('label');
                if (label) {
                    label.classList.remove('bg-blue-50', 'border-blue-300');
                    if (checkbox.checked) {
                        label.classList.add('bg-blue-50', 'border-blue-300');
                    }
                }
            });
        }

        showNotification('템플릿 적용 완료 (파란 배경 = 템플릿 값)', 'success');
    }
}

// 폼 제출
document.getElementById('orderForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {};
    
    // FormData를 객체로 변환
    for (let [key, value] of formData.entries()) {
        if (key === 'accessories[]') {
            if (!data.accessories) data.accessories = [];
            data.accessories.push(value);
        } else {
            data[key] = value;
        }
    }
    
    // 부자재를 쉼표로 구분된 문자열로 변환
    if (data.accessories) {
        data.accessories = data.accessories.join(', ');
    }
    
    const result = await apiCall('/api/orders.php', 'POST', data);
    
    if (result) {
        showNotification('주문서가 생성되었습니다.', 'success');
        setTimeout(() => {
            window.location.href = '/index.php';
        }, 1500);
    }
});

// 폼 초기화
function resetForm() {
    if (confirm('입력한 내용을 모두 초기화하시겠습니까?')) {
        document.getElementById('orderForm').reset();
        document.getElementById('order_date').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('manager_name').value = '<?php echo h($_SESSION['admin_name'] ?? ''); ?>';
        // 템플릿 스타일 초기화
        resetTemplateFieldStyles();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
