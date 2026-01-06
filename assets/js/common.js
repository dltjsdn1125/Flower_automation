// 공통 JavaScript 함수 (성능 최적화)
(function() {
    'use strict';
    
    // API 호출 함수 (캐싱 및 에러 처리 개선)
    window.apiCall = async function(url, method = 'GET', data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            
            // 응답이 비어있는지 확인
            const text = await response.text();
            if (!text || text.trim() === '') {
                console.error('API Error: Empty response');
                showNotification('서버 응답이 비어있습니다.', 'error');
                return null;
            }
            
            const result = JSON.parse(text);
            
            if (!result.success) {
                showNotification(result.message || '오류가 발생했습니다.', 'error');
                return null;
            }
            
            return result;
        } catch (error) {
            console.error('API Error:', error);
            showNotification('서버 오류가 발생했습니다.', 'error');
            return null;
        }
    };

    // 알림 표시 (개선된 버전)
    window.showNotification = function(message, type = 'success') {
        // 기존 알림 제거
        const existing = document.querySelector('.notification-toast');
        if (existing) {
            existing.remove();
        }
        
        const notification = document.createElement('div');
        notification.className = `notification-toast fixed top-4 right-4 px-6 py-4 rounded-xl shadow-lg z-50 transform transition-all duration-300 ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        } text-white`;
        notification.textContent = message;
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        
        document.body.appendChild(notification);
        
        // 애니메이션
        requestAnimationFrame(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        });
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    };

    // 페이지 전환 시 로딩 표시 (성능 최적화: 사용하지 않음)
    let loadingTimeout;
    // 페이지 전환 시 로딩 표시 (성능 최적화: 완전히 제거)
    window.showPageLoading = function() {
        // 페이지 전환 속도 개선을 위해 로딩 표시 비활성화
        // 빠른 페이지 전환 시 불필요한 DOM 조작 제거
    };

    window.hidePageLoading = function() {
        // 로딩 표시가 없으므로 아무 작업도 하지 않음
    };

    // closest 폴리필 (호환성 개선)
    function findClosest(element, selector) {
        if (element.closest) {
            return element.closest(selector);
        }
        while (element && element.nodeType === 1) {
            if (element.matches && element.matches(selector)) {
                return element;
            }
            element = element.parentElement;
        }
        return null;
    }

    // prefetch 제거: 페이지 이동 속도 개선을 위해 비활성화
    // Supabase REST API 호출이 많아 prefetch가 오히려 성능 저하를 유발할 수 있음

    // 링크 클릭 시 즉시 이동 (로딩 표시 제거로 속도 개선)
    // 페이지 전환이 빠르면 로딩 표시가 불필요하므로 제거
    // document.addEventListener('click', function(e) {
    //     const link = findClosest(e.target, 'a[href]');
    //     if (link && link.hostname === window.location.hostname && !link.hasAttribute('target')) {
    //         const href = link.getAttribute('href');
    //         if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
    //             // 로딩 표시 제거: 페이지 전환 속도 개선
    //         }
    //     }
    // }, true);

    // 페이지 로드 완료 시 로딩 숨김 (성능 최적화: 즉시 실행)
    hidePageLoading();
})();
