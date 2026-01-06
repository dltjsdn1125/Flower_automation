# XAMPP 자동 설치 스크립트

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "XAMPP 자동 설치" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$installer = "$env:USERPROFILE\Downloads\xampp-installer.exe"

if (-not (Test-Path $installer)) {
    Write-Host "XAMPP 설치 파일을 찾을 수 없습니다." -ForegroundColor Red
    Write-Host "다운로드 중..." -ForegroundColor Yellow
    
    $xamppUrl = "https://sourceforge.net/projects/xampp/files/XAMPP%20Windows/8.2.12/xampp-windows-x64-8.2.12-0-VS16-installer.exe/download"
    
    try {
        Invoke-WebRequest -Uri $xamppUrl -OutFile $installer -UseBasicParsing
        Write-Host "다운로드 완료!" -ForegroundColor Green
    } catch {
        Write-Host "다운로드 실패: $_" -ForegroundColor Red
        exit 1
    }
}

Write-Host "XAMPP 설치를 시작합니다..." -ForegroundColor Yellow
Write-Host "설치 창이 열리면 'Next' 버튼을 클릭하여 설치를 진행하세요." -ForegroundColor Yellow
Write-Host ""

# 설치 파일 실행
Start-Process -FilePath $installer -Wait

Write-Host ""
Write-Host "설치가 완료되었습니다!" -ForegroundColor Green
Write-Host ""

# XAMPP 설치 확인
if (Test-Path "C:\xampp\php\php.exe") {
    Write-Host "XAMPP가 성공적으로 설치되었습니다!" -ForegroundColor Green
    Write-Host ""
    Write-Host "다음 단계:" -ForegroundColor Cyan
    Write-Host "1. XAMPP Control Panel 실행" -ForegroundColor White
    Write-Host "2. Apache와 MySQL 시작" -ForegroundColor White
    Write-Host "3. 프로젝트를 C:\xampp\htdocs\flower-order 에 복사" -ForegroundColor White
    Write-Host ""
    
    # XAMPP Control Panel 실행 제안
    $response = Read-Host "XAMPP Control Panel을 지금 실행하시겠습니까? (Y/N)"
    if ($response -eq "Y" -or $response -eq "y") {
        Start-Process "C:\xampp\xampp-control.exe"
    }
} else {
    Write-Host "XAMPP 설치를 확인할 수 없습니다." -ForegroundColor Yellow
    Write-Host "수동으로 설치를 완료해주세요." -ForegroundColor Yellow
}
