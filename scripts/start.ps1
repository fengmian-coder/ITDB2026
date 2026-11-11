# WorldCup Journey - C/D Windows 本地一键初始化与启动，B 协助实现。
[CmdletBinding()]
param(
    [ValidateRange(1, 65535)][int]$Port = 8080,
    [string]$Php = 'php',
    [string]$Composer = 'composer',
    [switch]$PrepareOnly
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$previousEnvironment = $env:YII_ENV
$previousPath = $env:PATH
$previousOutputEncoding = $OutputEncoding
Push-Location $projectRoot
try {
    $phpCommand = (Get-Command $Php -ErrorAction Stop).Source
    $version = & $phpCommand -r 'echo PHP_MAJOR_VERSION, chr(46), PHP_MINOR_VERSION;'
    if ($LASTEXITCODE -ne 0 -or $version -ne '8.2') { throw 'PHP 8.2.x is required.' }
    $extensions = & $phpCommand -m
    $requiredExtensions = @('pdo_mysql','mbstring','openssl','intl','curl','zip','gd','fileinfo')
    $missing = @($requiredExtensions | Where-Object { $extensions -notcontains $_ })
    if ($LASTEXITCODE -ne 0 -or $missing.Count -gt 0) { throw "Missing PHP extensions: $($missing -join ', ')" }
    # 仅调整本次进程 PATH，确保 Composer 和子进程使用同一套 PHP。
    $env:PATH = (Split-Path -Parent $phpCommand) + [IO.Path]::PathSeparator + $previousPath
    $env:YII_ENV = 'prod'
    $OutputEncoding = [System.Text.UTF8Encoding]::new($false)
    $composerCommand = (Get-Command $Composer -ErrorAction Stop).Source
    $composerVersion = & $composerCommand --version --no-ansi
    if ($LASTEXITCODE -ne 0 -or ($composerVersion -join "`n") -notmatch 'Composer version 2\.') {
        throw 'Composer 2.x is required.'
    }
    if (-not $PrepareOnly) {
        $listener = [Net.Sockets.TcpListener]::new([Net.IPAddress]::Loopback, $Port)
        try { $listener.Start() } finally { $listener.Stop() }
    }
    & $phpCommand scripts/configure-local.php --check
    if ($LASTEXITCODE -eq 2) {
        $databaseHost = Read-Host 'MySQL host [127.0.0.1]'
        if ([string]::IsNullOrWhiteSpace($databaseHost)) { $databaseHost = '127.0.0.1' }
        $databasePort = Read-Host 'MySQL port [3306]'
        if ([string]::IsNullOrWhiteSpace($databasePort)) { $databasePort = '3306' }
        $databaseName = Read-Host 'Database name [worldcup_journey]'
        if ([string]::IsNullOrWhiteSpace($databaseName)) { $databaseName = 'worldcup_journey' }
        $databaseUser = Read-Host 'MySQL username'
        $securePassword = Read-Host 'MySQL password' -AsSecureString
        $credential = [PSCredential]::new($databaseUser, $securePassword)
        $localConfig = @{host=$databaseHost; port=[int]$databasePort; database=$databaseName;
            username=$databaseUser; password=$credential.GetNetworkCredential().Password}
        $localConfig | ConvertTo-Json -Compress | & $phpCommand scripts/configure-local.php
        $localConfig.Clear()
        if ($LASTEXITCODE -ne 0) { throw 'Local database configuration failed.' }
    } elseif ($LASTEXITCODE -ne 0) { throw 'Unable to read local database configuration.' }
    & $composerCommand install --no-interaction --prefer-dist
    if ($LASTEXITCODE -ne 0) { throw 'Composer dependency installation failed.' }
    & $composerCommand check-platform-reqs
    if ($LASTEXITCODE -ne 0) { throw 'Composer platform validation failed.' }
    & $phpCommand yii bootstrap --interactive=0
    if ($LASTEXITCODE -ne 0) { throw 'Local configuration bootstrap failed.' }
    & $phpCommand yii deploy/prepare --interactive=0
    if ($LASTEXITCODE -ne 0) { throw 'Database/data initialization failed. See runtime/logs/app.log.' }
    Write-Host 'Database preparation complete.'
    Write-Host 'Create an administrator when needed with: php yii admin/create <username>'
    if (-not $PrepareOnly) {
        Write-Host "Ready at http://127.0.0.1:$Port/"
        Write-Host 'Press Ctrl+C to stop this local development server.'
        & $phpCommand yii serve 127.0.0.1 "--port=$Port"
        if ($LASTEXITCODE -ne 0) { throw 'Local server stopped with an error.' }
    }
} finally {
    $env:YII_ENV = $previousEnvironment
    $env:PATH = $previousPath
    $OutputEncoding = $previousOutputEncoding
    Pop-Location
}
