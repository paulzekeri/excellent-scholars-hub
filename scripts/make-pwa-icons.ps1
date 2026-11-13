# Generates the launcher icons referenced by public/manifest.webmanifest.
#
# The artwork is the school's existing 180x180 apple-touch icon, which is the
# largest clean copy of the mark in public/. Launchers draw an icon at 48-96 px,
# so rendering it at 192/512 is sharp on screen; replace public/images/
# apple-touch-icon.png with a 512x512 original when one exists and re-run.
#
#   powershell -ExecutionPolicy Bypass -File scripts\make-pwa-icons.ps1
#
param(
    [string]$Source = (Join-Path $PSScriptRoot '..\public\images\apple-touch-icon.png'),
    [string]$OutDir = (Join-Path $PSScriptRoot '..\public\icons')
)

Add-Type -AssemblyName System.Drawing

if (-not (Test-Path $Source)) {
    throw "Artwork not found: $Source"
}

New-Item -ItemType Directory -Force -Path $OutDir | Out-Null

$art = [System.Drawing.Image]::FromFile((Resolve-Path $Source))

function New-Icon {
    param([int]$Size, [string]$File, [string]$Background, [double]$ArtworkRatio)

    $bitmap = New-Object System.Drawing.Bitmap($Size, $Size)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $graphics.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $graphics.Clear([System.Drawing.ColorTranslator]::FromHtml($Background))

    $draw = [int]($Size * $ArtworkRatio)
    $offset = [int](($Size - $draw) / 2)
    $graphics.DrawImage($art, $offset, $offset, $draw, $draw)
    $graphics.Dispose()

    $bitmap.Save((Join-Path $OutDir $File), [System.Drawing.Imaging.ImageFormat]::Png)
    $bitmap.Dispose()
}

# Full-bleed launcher icons on white, and an Android adaptive icon whose artwork
# stays inside the safe zone so a round/squircle mask cannot clip the mark.
New-Icon -Size 192 -File 'icon-192.png' -Background '#ffffff' -ArtworkRatio 1.0
New-Icon -Size 512 -File 'icon-512.png' -Background '#ffffff' -ArtworkRatio 1.0
New-Icon -Size 512 -File 'maskable-512.png' -Background '#0a2550' -ArtworkRatio 0.72

$art.Dispose()

# Status-bar badge (Android draws it as a silhouette).
Copy-Item (Join-Path $PSScriptRoot '..\public\images\favicon-32x32.png') (Join-Path $OutDir 'icon-32.png') -Force

Write-Host "PWA icons written to $OutDir"
