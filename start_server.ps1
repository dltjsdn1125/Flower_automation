# 발주 관리 시스템 개발 서버 시작 스크립트

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "발주 관리 시스템 개발 서버 시작" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# PHP 경로 확인
$phpPath = $null

# XAMPP 경로 확인
if (Test-Path "C:\xampp\php\php.exe") {
    $phpPath = "C:\xampp\php\php.exe"
    Write-Host "[XAMPP] PHP 경로를 찾았습니다." -ForegroundColor Green
}
# WAMP 경로 확인
elseif (Test-Path "C:\wamp64\bin\php\php8.2.0\php.exe") {
    $phpPath = "C:\wamp64\bin\php\php8.2.0\php.exe"
    Write-Host "[WAMP] PHP 경로를 찾았습니다." -ForegroundColor Green
}
# 시스템 PATH에서 PHP 찾기
else {
    $phpPath = Get-Command php -ErrorAction SilentlyContinue
    if ($phpPath) {
        $phpPath = $phpPath.Source
        Write-Host "[시스템] PHP 경로를 찾았습니다." -ForegroundColor Green
    }
}

if (-not $phpPath) {
    Write-Host "PHP를 찾을 수 없습니다." -ForegroundColor Red
    Write-Host ""
    Write-Host "설치 방법:" -ForegroundColor Yellow
    Write-Host "1. XAMPP 다운로드: https://www.apachefriends.org/" -ForegroundColor White
    Write-Host "2. 또는 PHP 직접 설치: https://windows.php.net/download/" -ForegroundColor White
    Write-Host ""
    Write-Host "XAMPP 사용 시:" -ForegroundColor Yellow
    Write-Host "- C:\xampp\php\php.exe 경로를 위에 추가하세요" -ForegroundColor White
    Write-Host ""
    Read-Host "아무 키나 누르세요"
    exit 1
}

Write-Host ""
Write-Host "서버 시작 중..." -ForegroundColor Yellow
Write-Host "브라우저에서 http://localhost:8000 접속하세요" -ForegroundColor Green
Write-Host "종료하려면 Ctrl+C를 누르세요" -ForegroundColor Yellow
Write-Host ""

# 현재 디렉토리로 이동
Set-Location $PSScriptRoot

# PHP 내장 서버 실행
& $phpPath -S localhost:8000 -t .
