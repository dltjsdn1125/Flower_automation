@echo off
echo ========================================
echo 발주 관리 시스템 개발 서버 시작
echo ========================================
echo.

REM PHP 경로 확인 (XAMPP 사용 시)
if exist "C:\xampp\php\php.exe" (
    set PHP_PATH=C:\xampp\php\php.exe
    echo [XAMPP] PHP 경로를 찾았습니다.
) else if exist "C:\wamp64\bin\php\php8.2.0\php.exe" (
    set PHP_PATH=C:\wamp64\bin\php\php8.2.0\php.exe
    echo [WAMP] PHP 경로를 찾았습니다.
) else (
    echo PHP를 찾을 수 없습니다.
    echo.
    echo 설치 방법:
    echo 1. XAMPP 다운로드: https://www.apachefriends.org/
    echo 2. 또는 PHP 직접 설치: https://windows.php.net/download/
    echo.
    echo XAMPP 사용 시:
    echo - C:\xampp\php\php.exe 경로를 위에 추가하세요
    echo.
    pause
    exit /b 1
)

echo.
echo 서버 시작 중...
echo 브라우저에서 http://localhost:8000 접속하세요
echo 종료하려면 Ctrl+C를 누르세요
echo.

%PHP_PATH% -S localhost:8000 -t .
