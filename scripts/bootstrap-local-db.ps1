$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$workspaceRoot = Split-Path -Parent $projectRoot
$mysqlBin = 'C:\xampp\mysql\bin'
$mysqld = Join-Path $mysqlBin 'mysqld.exe'
$mysql = Join-Path $mysqlBin 'mysql.exe'
$installer = Join-Path $mysqlBin 'mysql_install_db.exe'
$dataRoot = Join-Path $workspaceRoot '.local-mariadb'
$dataDir = Join-Path $dataRoot 'data'
$myIni = Join-Path $dataDir 'my.ini'

function Invoke-MySqlCommand {
    param(
        [string] $Sql,
        [string] $Database = ''
    )

    $args = @('-u', 'root', '--protocol=tcp', '-h', '127.0.0.1', '-P', '3307')
    if ($Database -ne '') {
        $args += $Database
    }
    $args += @('-e', $Sql)

    & $mysql @args
    if ($LASTEXITCODE -ne 0) {
        throw "MySQL command gagal: $Sql"
    }
}

function Invoke-MySqlFile {
    param(
        [string] $FilePath,
        [string] $Database
    )

    Get-Content $FilePath -Raw | & $mysql -u root --protocol=tcp -h 127.0.0.1 -P 3307 $Database
    if ($LASTEXITCODE -ne 0) {
        throw "Import SQL gagal: $FilePath"
    }
}

if (!(Test-Path $mysqld) -or !(Test-Path $mysql) -or !(Test-Path $installer)) {
    throw 'Binary MariaDB XAMPP tidak ditemukan di C:\xampp\mysql\bin'
}

if (!(Test-Path $dataDir)) {
    New-Item -ItemType Directory -Path $dataRoot -Force | Out-Null
    & $installer --datadir=$dataDir --port=3307 | Out-Null
}

if (!(Test-Path $myIni)) {
    @"
[mysqld]
datadir=$($dataDir -replace '\\','/')
port=3307
[client]
port=3307
"@ | Set-Content -Path $myIni -NoNewline
}

$ready = $false
for ($attempt = 0; $attempt -lt 2 -and -not $ready; $attempt++) {
    try {
        Invoke-MySqlCommand -Sql 'SELECT 1'
        $ready = $true
        break
    } catch {
        cmd /c start "" /b "$mysqld" --defaults-file=$myIni | Out-Null
        for ($retry = 0; $retry -lt 20; $retry++) {
            Start-Sleep -Seconds 1
            try {
                Invoke-MySqlCommand -Sql 'SELECT 1'
                $ready = $true
                break
            } catch {
            }
        }
    }
}

if (-not $ready) {
    throw 'MariaDB lokal tidak berhasil hidup di 127.0.0.1:3307'
}

Invoke-MySqlCommand -Sql 'DROP DATABASE IF EXISTS starstyle; CREATE DATABASE starstyle CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;'
Invoke-MySqlFile -FilePath (Join-Path $projectRoot 'database\schema.sql') -Database 'starstyle'
Invoke-MySqlFile -FilePath (Join-Path $projectRoot 'database\seeders\demo_seed.sql') -Database 'starstyle'

Write-Output 'Local MariaDB StarStyle siap di 127.0.0.1:3307'