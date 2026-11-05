$workspace = "c:\Users\Administrator\.gemini\antigravity\scratch\laravel-tasks"
$destDir = "C:\Users\Administrator\Downloads\taf_oil_petroleum_project"
$destZip = "C:\Users\Administrator\Downloads\taf_oil_petroleum_full_project.zip"

Write-Output "Packaging complete project..."

# 1. Ensure target directory
if (Test-Path -Path $destDir) {
    Remove-Item -Path $destDir -Recurse -Force
}
New-Item -ItemType Directory -Path $destDir -Force | Out-Null

# 2. Exclude list
$excludeDirs = @('.git', '.github', '.ai', 'node_modules', '.phpunit.cache')

# 3. Copy files & directories recursively
Get-ChildItem -Path $workspace -Force | Where-Object {
    $name = $_.Name
    $excludeDirs -notcontains $name
} | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination $destDir -Recurse -Force
}

# 4. Remove any cache / log files inside copied storage
$storageTmp = "$destDir\storage"
if (Test-Path -Path "$storageTmp\framework\cache\data") {
    Get-ChildItem -Path "$storageTmp\framework\cache\data" -Recurse | Remove-Item -Force -ErrorAction SilentlyContinue
}
if (Test-Path -Path "$storageTmp\framework\sessions") {
    Get-ChildItem -Path "$storageTmp\framework\sessions" -Recurse | Remove-Item -Force -ErrorAction SilentlyContinue
}
if (Test-Path -Path "$storageTmp\framework\views") {
    Get-ChildItem -Path "$storageTmp\framework\views" -Recurse | Remove-Item -Force -ErrorAction SilentlyContinue
}

# 5. Create Zip Archive
if (Test-Path -Path $destZip) {
    Remove-Item -Path $destZip -Force
}

Write-Output "Compressing archive to $destZip ..."
Compress-Archive -Path "$destDir\*" -DestinationPath $destZip -Force

Write-Output "========================================================="
Write-Output "SUCCESS: COMPLETE PROJECT PACKAGED SUCCESSFULLY!"
Write-Output "========================================================="
Write-Output "Uncompressed Directory: $destDir"
Write-Output "ZIP Archive:            $destZip"
Get-Item -Path $destZip | Select-Object Name, Length, LastWriteTime
