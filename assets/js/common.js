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
            const result = await response.json();
            
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

    // 페이지 전환 시 로딩 표시 (개선된 버전: 즉시 표시)
    let loadingTimeout;
    window.showPageLoading = function() {
        // 이미 로딩 중이면 무시
        if (document.querySelector('.page-loading')) return;
        
        const loader = document.createElement('div');
        loader.className = 'page-loading fixed inset-0 bg-white/90 backdrop-blur-md z-50 flex items-center justify-center';
        loader.innerHTML = '<div class="flex flex-col items-center gap-4"><div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div><p class="text-slate-700 font-bold text-lg">페이지 로딩 중...</p><p class="text-slate-500 text-sm">잠시만 기다려주세요</p></div>';
        loader.style.opacity = '0';
        loader.style.transition = 'opacity 0.2s ease-in';
        document.body.appendChild(loader);
        
        // 즉시 표시 (애니메이션)
        requestAnimationFrame(() => {
            loader.style.opacity = '1';
        });
        
        // 타임아웃 설정 (10초)
        loadingTimeout = setTimeout(() => {
            if (loader.parentNode) {
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 200);
            }
        }, 10000);
    };

    window.hidePageLoading = function() {
        const loader = document.querySelector('.page-loading');
        if (loader) {
            clearTimeout(loadingTimeout);
            loader.style.opacity = '0';
            setTimeout(() => {
                if (loader.parentNode) {
                    loader.remove();
                }
            }, 200);
        }
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

    // 링크 hover 시 prefetch로 미리 로드 (성능 개선 - 디바운싱 적용)
    let prefetchedUrls = new Set();
    let prefetchTimeout = null;
    document.addEventListener('mouseenter', function(e) {
        // 디바운싱: 300ms 후에만 prefetch 실행
        if (prefetchTimeout) {
            clearTimeout(prefetchTimeout);
        }
        prefetchTimeout = setTimeout(function() {
            const link = findClosest(e.target, 'a[href]');
            if (link && link.hostname === window.location.hostname && !link.hasAttribute('target')) {
                const href = link.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !prefetchedUrls.has(href)) {
                    // 최대 3개까지만 prefetch
                    if (prefetchedUrls.size < 3) {
                        const linkTag = document.createElement('link');
                        linkTag.rel = 'prefetch';
                        linkTag.href = href;
                        document.head.appendChild(linkTag);
                        prefetchedUrls.add(href);
                    }
                }
            }
        }, 300);
    }, true);

    // 링크 클릭 시 로딩 표시 (즉시 표시)
    document.addEventListener('click', function(e) {
        const link = findClosest(e.target, 'a[href]');
        if (link && link.hostname === window.location.hostname && !link.hasAttribute('target')) {
            const href = link.getAttribute('href');
            if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                // 즉시 로딩 표시 (사용자 피드백)
                showPageLoading();
            }
        }
    }, true);

    // 페이지 로드 완료 시 로딩 숨김
    if (document.readyState === 'complete') {
        hidePageLoading();
    } else {
        window.addEventListener('load', hidePageLoading);
    }
})();
