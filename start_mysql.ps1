# MySQL 시작 스크립트

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MySQL 시작" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# XAMPP MySQL 경로 확인
$xamppPaths = @(
    "C:\xampp\mysql\bin\mysqld.exe",
    "C:\xampp\mysql\bin\mysql.exe"
)

$mysqlFound = $false
foreach ($path in $xamppPaths) {
    if (Test-Path $path) {
        Write-Host "XAMPP MySQL 발견: $path" -ForegroundColor Green
        $mysqlFound = $true
        break
    }
}

if (-not $mysqlFound) {
    Write-Host "XAMPP MySQL을 찾을 수 없습니다." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "해결 방법:" -ForegroundColor Cyan
    Write-Host "1. XAMPP Control Panel을 열어주세요" -ForegroundColor White
    Write-Host "2. MySQL 옆의 'Start' 버튼을 클릭하세요" -ForegroundColor White
    Write-Host ""
    Write-Host "XAMPP Control Panel 열기 시도 중..." -ForegroundColor Yellow
    $xamppControl = "C:\xampp\xampp-control.exe"
    if (Test-Path $xamppControl) {
        Start-Process $xamppControl
        Write-Host "XAMPP Control Panel을 열었습니다." -ForegroundColor Green
    } else {
        Write-Host "XAMPP Control Panel을 찾을 수 없습니다." -ForegroundColor Red
        Write-Host "수동으로 XAMPP를 실행하고 MySQL을 시작해주세요." -ForegroundColor Yellow
    }
} else {
    Write-Host "MySQL 서비스 확인 중..." -ForegroundColor Yellow
    $mysqlService = Get-Service -Name "*mysql*" -ErrorAction SilentlyContinue
    if ($mysqlService) {
        if ($mysqlService.Status -eq 'Running') {
            Write-Host "MySQL이 이미 실행 중입니다!" -ForegroundColor Green
        } else {
            Write-Host "MySQL 서비스 시작 중..." -ForegroundColor Yellow
            Start-Service -Name $mysqlService.Name -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 2
            if ((Get-Service -Name $mysqlService.Name).Status -eq 'Running') {
                Write-Host "MySQL 서비스가 시작되었습니다!" -ForegroundColor Green
            } else {
                Write-Host "MySQL 서비스 시작 실패. XAMPP Control Panel에서 수동으로 시작해주세요." -ForegroundColor Red
            }
        }
    } else {
        Write-Host "MySQL 서비스를 찾을 수 없습니다." -ForegroundColor Yellow
        Write-Host "XAMPP Control Panel에서 MySQL을 시작해주세요." -ForegroundColor Yellow
        $xamppControl = "C:\xampp\xampp-control.exe"
        if (Test-Path $xamppControl) {
            Start-Process $xamppControl
        }
    }
}

Write-Host ""
Write-Host "MySQL 연결 테스트 중..." -ForegroundColor Yellow
Start-Sleep -Seconds 2

$testConnection = Test-NetConnection -ComputerName localhost -Port 3306 -InformationLevel Quiet -WarningAction SilentlyContinue
if ($testConnection) {
    Write-Host "✅ MySQL이 정상적으로 실행 중입니다!" -ForegroundColor Green
    Write-Host ""
    Write-Host "다음 단계:" -ForegroundColor Cyan
    Write-Host "1. 브라우저에서 http://localhost:8000/setup_database.php 를 열어주세요" -ForegroundColor White
    Write-Host "2. 자동으로 데이터베이스가 생성됩니다" -ForegroundColor White
} else {
    Write-Host "❌ MySQL이 아직 실행되지 않았습니다." -ForegroundColor Red
    Write-Host ""
    Write-Host "XAMPP Control Panel에서 MySQL을 시작한 후 다시 시도해주세요." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "아무 키나 누르면 종료됩니다..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
