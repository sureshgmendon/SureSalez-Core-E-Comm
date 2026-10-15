param(
    [Parameter(Mandatory=$true)]
    [string]$SourceDir,

    [Parameter(Mandatory=$false)]
    [string]$Prefix = "",

    [Parameter(Mandatory=$true)]
    [string]$DestinationZip
)

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$destDir = Split-Path -Path $DestinationZip -Parent
if (-not (Test-Path $destDir)) {
    New-Item -ItemType Directory -Path $destDir -Force | Out-Null
}

if (Test-Path $DestinationZip) {
    Remove-Item $DestinationZip -Force
}

$zip = [System.IO.Compression.ZipFile]::Open($DestinationZip, [System.IO.Compression.ZipArchiveMode]::Create)

$sourceResolved = (Resolve-Path $SourceDir).Path
$files = Get-ChildItem -Path $SourceDir -Recurse | Where-Object { -not $_.PSIsContainer }

foreach ($file in $files) {
    $rel = $file.FullName.Substring($sourceResolved.Length).TrimStart('\', '/').Replace('\', '/')
    $entryName = if ($Prefix -ne "") { "$Prefix/$rel" } else { $rel }
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
}

$zip.Dispose()
Write-Output "Successfully created $DestinationZip with $($files.Count) files."
