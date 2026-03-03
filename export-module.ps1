$ErrorActionPreference = 'Stop'

$moduleRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$configFile = Join-Path $moduleRoot 'config\config.php'
$distDir = Join-Path $moduleRoot 'dist'

if (-not (Test-Path $configFile)) {
    throw 'config\config.php not found.'
}

$configContent = Get-Content $configFile -Raw
$versionMatch = [regex]::Match($configContent, "'version'\s*=>\s*'([^']+)'")
$version = if ($versionMatch.Success) { $versionMatch.Groups[1].Value } else { 'dev' }

if (-not (Test-Path $distDir)) {
    New-Item -ItemType Directory -Path $distDir | Out-Null
}

$zipName = "rssnews-v$version.zip"
$zipPath = Join-Path $distDir $zipName

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

$tempRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("rssnews-export-" + [guid]::NewGuid().ToString('N'))
$tempModuleDir = Join-Path $tempRoot 'rssnews'

New-Item -ItemType Directory -Path $tempModuleDir -Force | Out-Null

Get-ChildItem $moduleRoot -Force | Where-Object {
    $_.Name -notin @('dist', '.git')
} | ForEach-Object {
    Copy-Item $_.FullName -Destination $tempModuleDir -Recurse -Force
}

Compress-Archive -Path (Join-Path $tempRoot 'rssnews') -DestinationPath $zipPath -Force
Remove-Item $tempRoot -Recurse -Force

Write-Output "Created package: $zipPath"
