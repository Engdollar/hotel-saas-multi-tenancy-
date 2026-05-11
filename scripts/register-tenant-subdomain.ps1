param(
    [Parameter(Mandatory = $true)]
    [string] $Subdomain,

    [string] $BaseDomain = "eelo-university.test",

    [string] $HostsPath = "C:\Windows\System32\drivers\etc\hosts"
)

$Subdomain = $Subdomain.Trim().ToLower()
$BaseDomain = $BaseDomain.Trim().ToLower()

if ([string]::IsNullOrWhiteSpace($Subdomain)) {
    throw "Subdomain is required."
}

$HostName = "$Subdomain.$BaseDomain"
$Entry = "127.0.0.1 $HostName"

if (-not (Test-Path $HostsPath)) {
    throw "Hosts file not found at $HostsPath"
}

$Current = Get-Content $HostsPath -ErrorAction Stop

if ($Current -contains $Entry) {
    Write-Host "$HostName is already mapped in the hosts file."
    exit 0
}

Add-Content -Path $HostsPath -Value $Entry -ErrorAction Stop
ipconfig /flushdns | Out-Null

Write-Host "Added $HostName to hosts and flushed DNS."