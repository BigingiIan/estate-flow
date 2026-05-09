param(
    [Parameter(Mandatory = $true)]
    [string]$SnapshotPath,
    [string]$EnvFile = ".env"
)

$ErrorActionPreference = "Stop"

function Get-EnvMap {
    param([string]$Path)

    if (-not (Test-Path $Path)) {
        throw "Environment file not found: $Path"
    }

    $map = @{}

    foreach ($line in Get-Content $Path) {
        if ([string]::IsNullOrWhiteSpace($line) -or $line.TrimStart().StartsWith("#")) {
            continue
        }

        $parts = $line -split "=", 2
        if ($parts.Count -ne 2) {
            continue
        }

        $map[$parts[0]] = $parts[1].Trim('"')
    }

    return $map
}

function Get-CommandPath {
    param([string[]]$Candidates)

    foreach ($candidate in $Candidates) {
        $command = Get-Command $candidate -ErrorAction SilentlyContinue
        if ($command) {
            return $command.Source
        }
    }

    return $null
}

if (-not (Test-Path $SnapshotPath)) {
    throw "Snapshot file not found: $SnapshotPath"
}

$envMap = Get-EnvMap -Path $EnvFile
$dbConnection = $envMap["DB_CONNECTION"]

if ($dbConnection -eq "sqlite") {
    Copy-Item $SnapshotPath $envMap["DB_DATABASE"] -Force
    Write-Host "Restored SQLite database from $SnapshotPath"
    exit 0
}

if ($dbConnection -ne "mysql") {
    throw "Unsupported DB_CONNECTION for restore automation: $dbConnection"
}

$mysqlTool = Get-CommandPath -Candidates @("mysql", "mariadb")
if (-not $mysqlTool) {
    throw "Neither mysql nor mariadb client is available on PATH."
}

$arguments = @(
    "--host=$($envMap['DB_HOST'])",
    "--port=$($envMap['DB_PORT'])",
    "--user=$($envMap['DB_USERNAME'])",
    "--password=$($envMap['DB_PASSWORD'])",
    $envMap["DB_DATABASE"]
)

Get-Content $SnapshotPath | & $mysqlTool @arguments

Write-Host "Restored MySQL database from $SnapshotPath"
