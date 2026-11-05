$sourceDir = "c:\Users\Administrator\.gemini\antigravity\scratch\laravel-tasks\erpnext"
$downloadDir = "C:\Users\Administrator\Downloads\erpnext_petroleum"
$zipPath = "C:\Users\Administrator\Downloads\erpnext_petroleum_customization.zip"

if (-not (Test-Path -Path $downloadDir)) {
    New-Item -ItemType Directory -Path $downloadDir -Force | Out-Null
}

Copy-Item -Path "$sourceDir\*" -Destination $downloadDir -Recurse -Force

if (Test-Path -Path $zipPath) {
    Remove-Item -Path $zipPath -Force
}

Compress-Archive -Path "$downloadDir\*" -DestinationPath $zipPath -Force

Write-Output "SUCCESS: Package created."
Get-ChildItem -Path $downloadDir | Select-Object Name, Length
Get-Item -Path $zipPath | Select-Object FullName, Length
