<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = '인수증 복수 생성';

$db = getDB();

include __DIR__ . '/includes/header.php';
?>

<header class="flex items-center justify-between whitespace-nowrap bg-white/30 backdrop-blur-md border-b border-white/40 px-8 py-5 sticky top-0 z-20">
<div class="flex items-center gap-4">
<button class="md:hidden text-slate-800" onclick="history.back()">
<span class="material-symbols-outlined">arrow_back</span>
</button>
<div>
<h2 class="text-slate-800 text-2xl font-bold leading-tight tracking-tight">인수증 복수 생성</h2>
<p class="text-slate-500 text-sm font-medium">동일한 정보로 여러 인수증을 한번에 생성</p>
</div>
</div>
</header>

<div class="flex-1 overflow-y-auto p-4 md:p-8">
<div class="max-w-6xl mx-auto">
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<!-- 좌측: 인수증 정보 입력 폼 -->
<div class="lg:col-span-2">
<div class="glass-panel rounded-2xl p-6 md:p-8">
<form id="receiptForm" class="space-y-4 md:space-y-6">
<!-- 주문자 정보 -->
<div class="border-b border-slate-200 pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">주문자 정보</h3>
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">주문자명</label>
<input type="text" name="orderer_name" id="orderer_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">주문자 휴대폰번호1</label>
<input type="tel" name="orderer_phone1" id="orderer_phone1" placeholder="010-1234-5678" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">주문자 휴대폰번호2</label>
<input type="tel" name="orderer_phone2" id="orderer_phone2" placeholder="010-1234-5678" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
</div>
</div>

<!-- 수취인 정보 -->
<div class="border-b border-slate-200 pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">수취인 정보</h3>
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">수취인명</label>
<input type="text" name="recipient_name" id="recipient_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">수취인 휴대폰번호1</label>
<input type="tel" name="recipient_phone1" id="recipient_phone1" placeholder="010-1234-5678" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">수취인 휴대폰번호2</label>
<input type="tel" name="recipient_phone2" id="recipient_phone2" placeholder="010-1234-5678" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
</div>
</div>

<!-- 배달 정보 -->
<div class="border-b border-slate-200 pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">배달 정보</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">배달일시</label>
<input type="date" name="delivery_date" id="delivery_date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">배달상세시간</label>
<select name="delivery_detail_time" id="delivery_detail_time" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
<option value="">선택하세요</option>
<option value="오전">오전</option>
<option value="오후">오후</option>
<option value="저녁">저녁</option>
<option value="시간협의">시간협의</option>
</select>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">배달 시간</label>
<input type="time" name="delivery_time" id="delivery_time" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
</div>
</div>

<!-- 경조사 및 기타 정보 -->
<div class="border-b border-slate-200 pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">경조사 및 기타 정보</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">경조사어</label>
<input type="text" name="occasion_word" id="occasion_word" placeholder="예: 축하합니다, 감사합니다" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">보내는분</label>
<input type="text" name="sender_name" id="sender_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
</div>
</div>

<!-- 배달 주소 -->
<div class="border-b border-slate-200 pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">배달 주소</h3>
<div class="space-y-4">
<div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-4">
<div class="md:col-span-3">
<label class="block text-sm font-medium text-slate-700 mb-2">우편번호</label>
<div class="flex gap-2">
<input type="text" name="delivery_postcode" id="delivery_postcode" readonly placeholder="우편번호 검색" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-600">
<button type="button" onclick="openPostcode()" class="px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all text-sm font-medium whitespace-nowrap">
<span class="material-symbols-outlined text-sm align-middle">search</span> 검색
</button>
</div>
</div>
<div class="md:col-span-9">
<label class="block text-sm font-medium text-slate-700 mb-2">배달장소</label>
<input type="text" name="delivery_address" id="delivery_address" readonly placeholder="우편번호 검색 후 자동 입력됩니다" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-600">
</div>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">상세주소</label>
<input type="text" name="delivery_detail_address" id="delivery_detail_address" placeholder="상세 주소를 입력하세요 (예: 101호, 201동 등)" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
</div>
</div>
</div>

<!-- 배달 요청사항 -->
<div class="pb-4 md:pb-6">
<h3 class="text-slate-800 text-base md:text-lg font-bold mb-3 md:mb-4">배달 요청사항</h3>
<textarea name="delivery_request" id="delivery_request" rows="4" placeholder="배달 시 특별 요청사항을 입력하세요" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all resize-none"></textarea>
</div>

<!-- 버튼 -->
<div class="flex gap-4 pt-6 border-t border-slate-200">
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

<!-- 우측: 수량 선택 -->
<div class="lg:col-span-1">
<div class="glass-panel rounded-2xl p-6 max-h-[calc(100vh-8rem)] overflow-y-auto">
<h3 class="text-slate-800 text-lg font-bold mb-4">인수증 생성</h3>
<div class="space-y-4">
<div>
<label class="block text-sm font-medium text-slate-700 mb-2">생성 수량</label>
<div class="flex gap-2 mb-2">
<button type="button" onclick="setQuantity(5)" class="flex-1 px-3 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-all text-sm font-medium">5개</button>
<button type="button" onclick="setQuantity(10)" class="flex-1 px-3 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-all text-sm font-medium">10개</button>
<button type="button" onclick="setQuantity(20)" class="flex-1 px-3 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-all text-sm font-medium">20개</button>
</div>
<input type="number" id="receipt_count" min="1" max="100" value="1" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-center text-lg font-bold">
<p class="text-xs text-slate-500 mt-2 text-center">1~100개까지 입력 가능</p>
</div>
<div class="pt-4 border-t border-slate-200">
<button onclick="createReceipts()" class="w-full py-3 bg-black text-white font-bold rounded-xl hover:bg-slate-800 transition-all">
<span class="material-symbols-outlined align-middle">save</span> 저장
</button>
<p class="text-xs text-slate-500 mt-2 text-center">입력한 정보로 <span id="count_display">1</span>개의 인수증이 생성됩니다</p>
</div>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- 우편번호 검색 스크립트 (다음 주소 API) -->
<script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
// 수량 설정
function setQuantity(count) {
    document.getElementById('receipt_count').value = count;
    updateCountDisplay();
}

// 수량 표시 업데이트
function updateCountDisplay() {
    const count = document.getElementById('receipt_count').value;
    document.getElementById('count_display').textContent = count;
}

document.getElementById('receipt_count').addEventListener('input', updateCountDisplay);

// 우편번호 검색
function openPostcode() {
    if (typeof daum === 'undefined' || typeof daum.Postcode === 'undefined') {
        alert('우편번호 검색 서비스를 불러올 수 없습니다. 페이지를 새로고침해주세요.');
        return;
    }
    
    // 팝업 레이어 먼저 표시
    showPostcodeLayer();
    
    // 컨테이너가 생성될 때까지 대기
    setTimeout(function() {
        const container = document.getElementById('postcodeContainer');
        if (!container) {
            alert('우편번호 검색 창을 불러올 수 없습니다.');
            closePostcodeLayer();
            return;
        }
        
        new daum.Postcode({
            width: '100%',
            height: '100%',
            maxSuggestItems: 5,
            oncomplete: function(data) {
                let addr = '';
                
                // 사용자가 선택한 주소 타입에 따라 해당 주소를 가져온다
                if (data.userSelectedType === 'R') {
                    // 도로명 주소를 선택한 경우
                    addr = data.roadAddress;
                } else {
                    // 지번 주소를 선택한 경우
                    addr = data.jibunAddress;
                }
                
                // 우편번호와 주소 정보를 해당 필드에 넣는다
                document.getElementById('delivery_postcode').value = data.zonecode;
                document.getElementById('delivery_address').value = addr;
                
                // 레이어 닫기
                closePostcodeLayer();
                
                // 커서를 상세주소 필드로 이동한다
                document.getElementById('delivery_detail_address').focus();
            },
            onclose: function(state) {
                // 팝업이 닫힐 때 레이어도 닫기
                if (state === 'FORCE_CLOSE') {
                    closePostcodeLayer();
                }
            }
        }).embed(container);
    }, 100);
}

// 인수증 복수 생성
async function createReceipts() {
    const count = parseInt(document.getElementById('receipt_count').value);
    
    if (count < 1 || count > 100) {
        alert('수량은 1~100 사이여야 합니다.');
        return;
    }
    
    const formData = new FormData(document.getElementById('receiptForm'));
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    data.count = count;
    
    if (!confirm(`${count}개의 인수증을 생성하시겠습니까?`)) {
        return;
    }
    
    const result = await apiCall('/api/receipts_bulk.php', 'POST', data);
    
    if (result) {
        showNotification(`${count}개의 인수증이 생성되었습니다.`, 'success');
        setTimeout(() => {
            // 캐시 방지를 위해 타임스탬프 추가 및 강제 새로고침
            const url = '/receipt_list.php?t=' + Date.now();
            // 페이지를 완전히 새로고침하기 위해 location.replace 사용
            window.location.replace(url);
        }, 1500);
    }
}

// 폼 초기화
function resetForm() {
    if (confirm('입력한 내용을 모두 초기화하시겠습니까?')) {
        document.getElementById('receiptForm').reset();
        document.getElementById('receipt_count').value = 1;
        updateCountDisplay();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
