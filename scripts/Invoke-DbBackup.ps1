param(
    [string]$EnvFile = ".env",
    [string]$OutputDir = "database/db-backups"
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

$envMap = Get-EnvMap -Path $EnvFile
$dbConnection = $envMap["DB_CONNECTION"]

New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"

if ($dbConnection -eq "sqlite") {
    $dbPath = $envMap["DB_DATABASE"]
    if (-not (Test-Path $dbPath)) {
        throw "SQLite database file not found: $dbPath"
    }

    $snapshotPath = Join-Path $OutputDir "backup-$timestamp.sqlite"
    Copy-Item $dbPath $snapshotPath -Force
    Write-Host "Created SQLite snapshot: $snapshotPath"
    exit 0
}

if ($dbConnection -ne "mysql") {
    throw "Unsupported DB_CONNECTION for backup automation: $dbConnection"
}

$dumpTool = Get-CommandPath -Candidates @("mysqldump", "mariadb-dump")
if (-not $dumpTool) {
    throw "Neither mysqldump nor mariadb-dump is available on PATH."
}

$snapshotPath = Join-Path $OutputDir "backup-$timestamp.sql"
$arguments = @(
    "--host=$($envMap['DB_HOST'])",
    "--port=$($envMap['DB_PORT'])",
    "--user=$($envMap['DB_USERNAME'])",
    "--password=$($envMap['DB_PASSWORD'])",
    "--single-transaction",
    "--quick",
    "--routines",
    "--triggers",
    $envMap["DB_DATABASE"]
)

& $dumpTool @arguments | Out-File -FilePath $snapshotPath -Encoding utf8

if (-not (Test-Path $snapshotPath) -or (Get-Item $snapshotPath).Length -eq 0) {
    throw "Database dump failed: snapshot file was not created correctly."
}

Write-Host "Created MySQL snapshot: $snapshotPath"
