<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = '인수증 상세';

$db = getDB();

// 인수증 ID 확인
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    header('Location: /receipt_list.php');
    exit;
}

// 인수증 조회
$stmt = $db->prepare("SELECT * FROM receipts WHERE id = ?");
$stmt->execute([$id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    header('Location: /receipt_list.php');
    exit;
}

// 주문 정보 조회 (연결된 경우)
$order = null;
if ($receipt['order_id']) {
    $stmt = $db->prepare("SELECT o.*, sc.name as sales_channel_name, fs.name as flower_shop_name 
        FROM orders o 
        LEFT JOIN sales_channels sc ON o.sales_channel_id = sc.id 
        LEFT JOIN flower_shops fs ON o.flower_shop_id = fs.id 
        WHERE o.id = ?");
    $stmt->execute([$receipt['order_id']]);
    $order = $stmt->fetch();
}

include __DIR__ . '/includes/header.php';
?>

<header class="flex items-center justify-between whitespace-nowrap bg-white/30 backdrop-blur-md border-b border-white/40 px-8 py-5 sticky top-0 z-20">
<div class="flex items-center gap-4">
<div>
<h2 class="text-slate-800 text-2xl font-bold leading-tight tracking-tight">인수증 상세</h2>
<p class="text-slate-500 text-sm font-medium">인수증번호: <?php echo h($receipt['receipt_number']); ?></p>
</div>
</div>
<div class="flex items-center gap-4">
<a href="/receipt_list.php" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-all">
목록으로
</a>
</div>
</header>

<div class="flex-1 overflow-y-auto p-4 md:p-8">
<div class="max-w-4xl mx-auto space-y-6">
<!-- 인수증 정보 카드 -->
<div class="glass-panel rounded-2xl p-8">
<div class="flex justify-between items-start mb-6">
<div>
<h3 class="text-2xl font-bold text-slate-800"><?php echo h($receipt['receipt_number']); ?></h3>
<p class="text-sm text-slate-500 mt-1">생성일: <?php echo formatDate($receipt['created_at'], 'Y-m-d H:i'); ?></p>
</div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<!-- 주문자 정보 -->
<div class="border-b md:border-b-0 md:border-r border-slate-200 pb-6 md:pb-0 md:pr-6">
<h4 class="text-lg font-bold text-slate-800 mb-4">주문자 정보</h4>
<div class="space-y-3">
<div>
<label class="text-xs text-slate-500">주문자명</label>
<p class="text-sm font-medium text-slate-800"><?php echo h($receipt['orderer_name'] ?? '-'); ?></p>
</div>
<div>
<label class="text-xs text-slate-500">휴대폰번호1</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['orderer_phone1'] ?? '-'); ?></p>
</div>
<div>
<label class="text-xs text-slate-500">휴대폰번호2</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['orderer_phone2'] ?? '-'); ?></p>
</div>
</div>
</div>

<!-- 수취인 정보 -->
<div class="pb-6 md:pb-0 md:pl-6">
<h4 class="text-lg font-bold text-slate-800 mb-4">수취인 정보</h4>
<div class="space-y-3">
<div>
<label class="text-xs text-slate-500">수취인명</label>
<p class="text-sm font-medium text-slate-800"><?php echo h($receipt['recipient_name'] ?? '-'); ?></p>
</div>
<div>
<label class="text-xs text-slate-500">휴대폰번호1</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['recipient_phone1'] ?? '-'); ?></p>
</div>
<div>
<label class="text-xs text-slate-500">휴대폰번호2</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['recipient_phone2'] ?? '-'); ?></p>
</div>
</div>
</div>
</div>

<!-- 배달 정보 -->
<div class="border-t border-slate-200 pt-6 mt-6">
<h4 class="text-lg font-bold text-slate-800 mb-4">배달 정보</h4>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<div>
<label class="text-xs text-slate-500">배달일시</label>
<p class="text-sm font-medium text-slate-800"><?php echo $receipt['delivery_date'] ? formatDate($receipt['delivery_date']) : '-'; ?></p>
</div>
<div>
<label class="text-xs text-slate-500">배달상세시간</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['delivery_detail_time'] ?? '-'); ?></p>
</div>
<div>
<label class="text-xs text-slate-500">배달 시간</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['delivery_time'] ?? '-'); ?></p>
</div>
</div>
</div>

<!-- 배달 주소 -->
<?php if ($receipt['delivery_address'] || $receipt['delivery_postcode']): ?>
<div class="border-t border-slate-200 pt-6 mt-6">
<h4 class="text-lg font-bold text-slate-800 mb-4">배달 주소</h4>
<div class="space-y-2">
<?php if ($receipt['delivery_postcode']): ?>
<div>
<label class="text-xs text-slate-500">우편번호</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['delivery_postcode']); ?></p>
</div>
<?php endif; ?>
<div>
<label class="text-xs text-slate-500">배달장소</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['delivery_address'] ?? '-'); ?></p>
</div>
<?php if ($receipt['delivery_detail_address']): ?>
<div>
<label class="text-xs text-slate-500">상세주소</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['delivery_detail_address']); ?></p>
</div>
<?php endif; ?>
</div>
</div>
<?php endif; ?>

<!-- 경조사 및 기타 정보 -->
<?php if ($receipt['occasion_word'] || $receipt['sender_name']): ?>
<div class="border-t border-slate-200 pt-6 mt-6">
<h4 class="text-lg font-bold text-slate-800 mb-4">경조사 및 기타 정보</h4>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<?php if ($receipt['occasion_word']): ?>
<div>
<label class="text-xs text-slate-500">경조사어</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['occasion_word']); ?></p>
</div>
<?php endif; ?>
<?php if ($receipt['sender_name']): ?>
<div>
<label class="text-xs text-slate-500">보내는분</label>
<p class="text-sm text-slate-700"><?php echo h($receipt['sender_name']); ?></p>
</div>
<?php endif; ?>
</div>
</div>
<?php endif; ?>

<!-- 배달 요청사항 -->
<?php if ($receipt['delivery_request']): ?>
<div class="border-t border-slate-200 pt-6 mt-6">
<h4 class="text-lg font-bold text-slate-800 mb-4">배달 요청사항</h4>
<p class="text-sm text-slate-700 whitespace-pre-wrap"><?php echo h($receipt['delivery_request']); ?></p>
</div>
<?php endif; ?>
</div>

<!-- 연결된 주문 정보 -->
<?php if ($order): ?>
<div class="glass-panel rounded-2xl p-8 mt-6">
<h4 class="text-lg font-bold text-slate-800 mb-4">연결된 주문 정보</h4>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div>
<label class="text-xs text-slate-500">주문번호</label>
<p class="text-sm font-medium text-slate-800"><?php echo h($order['order_number']); ?></p>
</div>
<div>
<label class="text-xs text-slate-500">판매처</label>
<p class="text-sm text-slate-700"><?php echo h($order['sales_channel_name'] ?? '-'); ?></p>
</div>
<div>
<label class="text-xs text-slate-500">화원사</label>
<p class="text-sm text-slate-700"><?php echo h($order['flower_shop_name'] ?? '-'); ?></p>
</div>
<div>
<label class="text-xs text-slate-500">주문금액</label>
<p class="text-sm font-medium text-slate-800"><?php echo number_format($order['order_amount']); ?>원</p>
</div>
</div>
</div>
<?php endif; ?>
</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
