$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Drawing

function Resize-Png {
	param(
		[Parameter(Mandatory=$true)][string]$Path,
		[int]$Width = 20,
		[int]$Height = 20
	)

	if (-not (Test-Path $Path)) {
		Write-Host "Missing: $Path"
		return
	}

	$full = (Resolve-Path $Path).Path
	$src = [System.Drawing.Image]::FromFile($full)

	$dst = New-Object System.Drawing.Bitmap $Width, $Height
	$dst.SetResolution($src.HorizontalResolution, $src.VerticalResolution)

	$g = [System.Drawing.Graphics]::FromImage($dst)
	$g.CompositingMode = [System.Drawing.Drawing2D.CompositingMode]::SourceCopy
	$g.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality
	$g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
	$g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
	$g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
	$g.Clear([System.Drawing.Color]::Transparent)

	# Center-crop to square and downscale to avoid distortion.
	$side = [Math]::Min($src.Width, $src.Height)
	$srcX = [Math]::Floor(($src.Width - $side) / 2)
	$srcY = [Math]::Floor(($src.Height - $side) / 2)
	$srcRect = New-Object System.Drawing.Rectangle $srcX, $srcY, $side, $side
	$dstRect = New-Object System.Drawing.Rectangle 0, 0, $Width, $Height

	$g.DrawImage($src, $dstRect, $srcRect, [System.Drawing.GraphicsUnit]::Pixel)

	$g.Dispose()
	$src.Dispose()

	$dst.Save($full, [System.Drawing.Imaging.ImageFormat]::Png)
	$dst.Dispose()

	Write-Host "Resized: $Path -> ${Width}x${Height}"
}

Resize-Png -Path (Join-Path $PSScriptRoot '..\\assets\\ico\\tours.svg')
Resize-Png -Path (Join-Path $PSScriptRoot '..\\assets\\ico\\destinations.svg')
Resize-Png -Path (Join-Path $PSScriptRoot '..\\assets\\ico\\bookings.svg')

Write-Host "`nCurrent sizes:"
foreach ($p in @(
	(Join-Path $PSScriptRoot '..\\assets\\ico\\tours.svg'),
	(Join-Path $PSScriptRoot '..\\assets\\ico\\destinations.svg'),
	(Join-Path $PSScriptRoot '..\\assets\\ico\\bookings.svg')
)) {
	$full = (Resolve-Path $p).Path
	$img = [System.Drawing.Image]::FromFile($full)
	$len = (Get-Item $full).Length
	Write-Host ("- " + (Split-Path $full -Leaf) + ": " + $img.Width + "x" + $img.Height + ", " + $len + " bytes")
	$img.Dispose()
}

