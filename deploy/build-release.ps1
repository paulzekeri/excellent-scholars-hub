<#
.SYNOPSIS
    Builds a self-contained ESH LMS release zip for cPanel (UAT or production).

.DESCRIPTION
    Copies the application (vendor included unless -SkipVendor) into a temp folder, leaving
    out secrets and runtime files, then zips it for upload through cPanel File Manager.
    Excluded: .git, .env, esh_lms.sql, node_modules, deploy/, storage logs + framework
    caches, public/storage (recreated on the server with `php artisan storage:link`).

.PARAMETER OutFile
    Destination zip. Defaults to deploy\esh-uat-release.zip.

.PARAMETER SkipVendor
    Leave vendor/ out - use this when the server runs `composer install` itself.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File deploy\build-release.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File deploy\build-release.ps1 -SkipVendor
#>
[CmdletBinding()]
param(
    [string]$OutFile = '',
    [switch]$SkipVendor
)

$ErrorActionPreference = 'Stop'
$scriptDir = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }

if (-not $OutFile) {
    $OutFile = Join-Path $scriptDir 'esh-uat-release.zip'
}

$root = Split-Path -Parent $scriptDir

if (-not (Test-Path (Join-Path $root 'artisan'))) {
    throw "Could not find artisan in $root. Keep this script inside the repository's deploy/ folder."
}

if (-not $SkipVendor -and -not (Test-Path (Join-Path $root 'vendor\autoload.php'))) {
    throw 'vendor/autoload.php is missing. Run "composer install --no-dev --optimize-autoloader" first, or pass -SkipVendor.'
}

$staging = Join-Path ([System.IO.Path]::GetTempPath()) ('esh-release-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $staging -Force | Out-Null

$excludeDirs = @(
    (Join-Path $root '.git'),
    (Join-Path $root '.github'),
    (Join-Path $root 'node_modules'),
    (Join-Path $root 'deploy'),
    (Join-Path $root 'storage\logs'),
    (Join-Path $root 'storage\framework\cache'),
    (Join-Path $root 'storage\framework\sessions'),
    (Join-Path $root 'storage\framework\views'),
    (Join-Path $root 'public\storage')
)

if ($SkipVendor) {
    $excludeDirs += (Join-Path $root 'vendor')
}

$excludeFiles = @('.env', 'esh_lms.sql', '.phpunit.result.cache', '*.zip')

Write-Host "Staging $root ..."
robocopy $root $staging /E /XD $excludeDirs /XF $excludeFiles /NFL /NDL /NJH /NJS /NP | Out-Null

if ($LASTEXITCODE -ge 8) {
    throw "robocopy failed with exit code $LASTEXITCODE"
}

# Laravel needs these folders to exist and be writable on the server.
foreach ($dir in @(
    'storage\app\public',
    'storage\framework\cache',
    'storage\framework\sessions',
    'storage\framework\views',
    'storage\logs',
    'bootstrap\cache'
)) {
    New-Item -ItemType Directory -Path (Join-Path $staging $dir) -Force | Out-Null
}

if (Test-Path $OutFile) {
    Remove-Item $OutFile -Force
}

$outDir = Split-Path -Parent $OutFile

if ($outDir -and -not (Test-Path $outDir)) {
    New-Item -ItemType Directory -Path $outDir -Force | Out-Null
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

# Build the archive entry-by-entry so every path uses forward slashes (some cPanel
# extractors treat backslash-separated entries as a single literal file name).
$stream = [System.IO.File]::Open($OutFile, [System.IO.FileMode]::Create)
$archive = New-Object System.IO.Compression.ZipArchive($stream, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    $baseLength = $staging.Length + 1

    Get-ChildItem -LiteralPath $staging -Recurse -File -Force | ForEach-Object {
        $entryName = $_.FullName.Substring($baseLength).Replace('\', '/')
        [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive,
            $_.FullName,
            $entryName,
            [System.IO.Compression.CompressionLevel]::Optimal
        )
    }
} finally {
    $archive.Dispose()
    $stream.Dispose()
}

Remove-Item $staging -Recurse -Force

$sizeMb = '{0:N1} MB' -f ((Get-Item $OutFile).Length / 1MB)

Write-Host "Release written to $OutFile ($sizeMb)"
Write-Host ''
Write-Host 'Next steps (see DEPLOYMENT.md):'
Write-Host '  1. Upload the zip to the app folder (outside public_html) and extract it.'
Write-Host '  2. Create .env from .env.uat.example, then import a database (UAT) or migrate (production).'
Write-Host '  3. Run: php artisan key:generate --force'
Write-Host '  4. Run: php artisan storage:link'
Write-Host '  5. Run: php artisan esh:health'
