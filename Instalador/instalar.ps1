param([switch]$SinPausa)
$ErrorActionPreference = 'Stop'
$package = Split-Path -Parent $PSScriptRoot
$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = New-Object Security.Principal.WindowsPrincipal($identity)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    $shell = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
    $arguments = '-NoProfile -ExecutionPolicy Bypass -File "' + $PSCommandPath + '"'
    if ($SinPausa) {
        $arguments += ' -SinPausa'
    }
    Start-Process -FilePath $shell -ArgumentList $arguments -Verb RunAs -Wait
    exit
}
$transcript = Join-Path $package ('instalacion-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.log')
$logging = $false
function Get-VerifiedDownload([string]$Url, [string]$Destination, [string]$Hash) {
    if (Test-Path -LiteralPath $Destination) {
        if ((Get-FileHash -LiteralPath $Destination -Algorithm SHA256).Hash -eq $Hash) {
            return
        }
        Remove-Item -LiteralPath $Destination
    }
    $partial = $Destination + '.partial'
    Write-Host ('Descargando ' + [IO.Path]::GetFileName($Destination) + '...')
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    $web = New-Object Net.WebClient
    try { $web.DownloadFile($Url, $partial) } finally { $web.Dispose() }
    if ((Get-FileHash -LiteralPath $partial -Algorithm SHA256).Hash -ne $Hash) {
        Remove-Item -LiteralPath $partial
        throw 'La descarga no coincide con la suma SHA-256 esperada. No se ejecutara. Consulta las fuentes en Documentacion y actualiza el paquete.'
    }
    Move-Item -LiteralPath $partial -Destination $Destination
}
function Find-Wamp {
    $candidates = @('C:\wamp64','D:\wamp64','C:\wamp','D:\wamp')
    foreach ($key in @('HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\{wampserver64}_is1','HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\{wampserver64}_is1')) {
        $entry = Get-ItemProperty -LiteralPath $key -ErrorAction SilentlyContinue
        if ($entry -and $entry.InstallLocation) {
            $candidates = @($entry.InstallLocation) + $candidates
        }
    }
    foreach ($candidate in ($candidates | Select-Object -Unique)) {
        if (-not (Test-Path -LiteralPath $candidate -PathType Container -ErrorAction SilentlyContinue)) {
            continue
        }
        $manager = Join-Path $candidate 'wampmanager.exe'
        if (Test-Path -LiteralPath $manager -PathType Leaf -ErrorAction SilentlyContinue) {
            return $candidate
        }
    }
    return $null
}
function New-KryzalShortcut([string]$wamp) {
    $launcherFolder = Join-Path $wamp 'kryzal'
    New-Item -ItemType Directory -Path $launcherFolder -Force | Out-Null
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'abrir.ps1') -Destination (Join-Path $launcherFolder 'abrir.ps1') -Force
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'kryzal-marca.ico') -Destination (Join-Path $launcherFolder 'kryzal-marca.ico') -Force
    $desktop = [Environment]::GetFolderPath('CommonDesktopDirectory')
    $shell = New-Object -ComObject WScript.Shell
    try {
        $shortcut = $shell.CreateShortcut((Join-Path $desktop 'KryZal.lnk'))
        $shortcut.TargetPath = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
        $shortcut.Arguments = '-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "' + (Join-Path $launcherFolder 'abrir.ps1') + '"'
        $shortcut.WorkingDirectory = $launcherFolder
        $shortcut.IconLocation = (Join-Path $launcherFolder 'kryzal-marca.ico') + ',0'
        $shortcut.Description = 'Abrir la web de KryZal'
        $shortcut.Save()
    } finally {
        if ($shortcut -and [Runtime.InteropServices.Marshal]::IsComObject($shortcut)) {
            [Runtime.InteropServices.Marshal]::ReleaseComObject($shortcut) | Out-Null
        }
        if ([Runtime.InteropServices.Marshal]::IsComObject($shell)) {
            [Runtime.InteropServices.Marshal]::ReleaseComObject($shell) | Out-Null
        }
    }
}
function Find-WampPhp([string]$wamp) {
    $managerConfig = Join-Path $wamp 'wampmanager.conf'
    if (Test-Path -LiteralPath $managerConfig) {
        $text = Get-Content -LiteralPath $managerConfig -Raw
        if ($text -match '(?m)^phpVersion\s*=\s*"([^"\r\n]+)"') {
            $current = Join-Path $wamp ('bin\php\php' + $Matches[1] + '\php.exe')
            if (Test-Path -LiteralPath $current) {
                return $current
            }
        }
    }
    $directories = Get-ChildItem -LiteralPath (Join-Path $wamp 'bin\php') -Directory | Where-Object Name -Match '^php8\.\d+\.\d+$' | Sort-Object { [version]($_.Name.Substring(3)) } -Descending
    foreach ($directory in $directories) {
        $candidate = Join-Path $directory.FullName 'php.exe'
        if (Test-Path -LiteralPath $candidate) {
            return $candidate
        }
    }
    throw 'No se encuentra un PHP 8 de Wamp. Revisa su instalacion.'
}
function Test-VcDependencies {
    $entries = @()
    foreach ($key in @('HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall\*','HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*')) {
        $entries += @(Get-ItemProperty -Path $key -ErrorAction SilentlyContinue | Where-Object { $_.DisplayName -match 'Microsoft Visual C\+\+' })
    }
    foreach ($architecture in @('x86','x64')) {
        foreach ($minimum in @([version]'10.0.40219',[version]'11.0.61030',[version]'12.0.40664',[version]'14.51.36247')) {
            $found = $false
            foreach ($entry in $entries) {
                $parsed = $null
                if ($entry.DisplayName -match $architecture -and [version]::TryParse([string]$entry.DisplayVersion,[ref]$parsed) -and $parsed.Major -eq $minimum.Major -and $parsed -ge $minimum) {
                    $found = $true; break
                }
            }
            if (-not $found) {
                return $false
            }
        }
    }
    return $true
}
function Install-KryzalFiles([string]$source, [string]$wamp, [string]$php, [string]$databaseTool) {
    $www = Join-Path $wamp 'www'
    if (-not (Test-Path -LiteralPath $www -PathType Container)) {
        throw 'No se encuentra la carpeta www de Wamp.'
    }
    $destination = Join-Path $www 'FKryzal'
    if ([IO.Path]::GetFullPath($source).TrimEnd('\') -eq [IO.Path]::GetFullPath($destination).TrimEnd('\')) {
        throw 'Extrae el paquete fuera de www antes de ejecutar el instalador.'
    }
    if (Test-Path -LiteralPath $destination) {
        $item = Get-Item -LiteralPath $destination
        if (($item.Attributes -band [IO.FileAttributes]::ReparsePoint) -ne 0) {
            throw 'FKryzal es un enlace a otra carpeta. El instalador no elimina carpetas enlazadas.'
        }
    }
    $connection = Join-Path $destination 'app\config.php'
    if (-not (Test-Path -LiteralPath $connection)) {
        $connection = Join-Path $source 'app\config.php'
    }
    Write-Host 'Esperando a que MySQL este disponible...'
    $ready = $false
    for ($attempt=0; $attempt -lt 45; $attempt++) {
        $savedPreference = $ErrorActionPreference
        try { $ErrorActionPreference = 'Continue'; $result = & $php $databaseTool '--config' $connection '--check' 2>&1; $code = $LASTEXITCODE } finally { $ErrorActionPreference = $savedPreference }
        if ($code -eq 0) {
            $ready = $true; break
        }
        Start-Sleep -Seconds 2
    }
    if (-not $ready) {
        throw ('MySQL no responde o la conexion no es valida. ' + ($result -join ' '))
    }
    $staging = Join-Path $package ('Instalador\temporal-' + [Guid]::NewGuid().ToString('N'))
    try {
        Copy-Item -LiteralPath $source -Destination $staging -Recurse
        Write-Host 'Reiniciando la base de datos de KryZal...'
        $result = & $php $databaseTool '--config' $connection '--output' (Join-Path $staging 'app\config.php') 2>&1
        if ($LASTEXITCODE -ne 0) {
            throw ($result -join ' ')
        }
        Write-Host ($result -join ' ')
        if (Test-Path -LiteralPath $destination) {
            Remove-Item -LiteralPath $destination -Recurse -Force
        }
        Move-Item -LiteralPath $staging -Destination $destination
    } finally {
        if (Test-Path -LiteralPath $staging) {
            Remove-Item -LiteralPath $staging -Recurse -Force
        }
    }
    return $destination
}
try {
    Start-Transcript -Path $transcript | Out-Null
    $logging = $true
    Write-Host ''
    Write-Host 'KRYZAL - INSTALACION PARA WINDOWS' -ForegroundColor Yellow
    Write-Host 'Cada ejecucion sustituye FKryzal y reinicia su base de datos. Los datos anteriores del proyecto se eliminan.'
    if (-not [Environment]::Is64BitOperatingSystem -or $env:PROCESSOR_ARCHITECTURE -eq 'ARM64') {
        throw 'Este paquete prepara Wamp para Windows x64. No es un instalador para Windows ARM.'
    }
    $source = Join-Path $package 'FKryzal'
    if (-not (Test-Path -LiteralPath (Join-Path $source 'index.php'))) {
        throw 'Descomprime el ZIP completo antes de abrir el instalador. Falta FKryzal\index.php.'
    }
    $wamp = Find-Wamp
    if ($wamp) {
        Write-Host ('Wamp encontrado: ' + $wamp) -ForegroundColor Green
        $choice = Read-Host 'Pulsa ENTER para reutilizarlo, o escribe otra carpeta de Wamp'
        if ($choice.Trim()) {
            $wamp = $choice.Trim().Trim('"')
        }
        if (-not (Test-Path -LiteralPath (Join-Path $wamp 'wampmanager.exe'))) {
            throw 'La carpeta seleccionada no contiene wampmanager.exe.'
        }
    } else {
        $choice = Read-Host 'Si tienes Wamp en otra carpeta, escribe su ruta. ENTER instala Wamp en C:\wamp64'
        if ($choice.Trim()) {
            $wamp = $choice.Trim().Trim('"')
            if (-not (Test-Path -LiteralPath (Join-Path $wamp 'wampmanager.exe'))) {
                throw 'No se encontro Wamp en esa carpeta.'
            }
        } else {
            $wamp = 'C:\wamp64'
            if (Test-Path -LiteralPath $wamp) {
                throw 'C:\wamp64 ya existe, pero no se detecta Wamp. No se instalara encima. Revisa esa carpeta antes de continuar.'
            }
            Write-Host 'Se descargaran Visual C++ y Wamp 3.4.0, unos 400 MB. Los instaladores aplican sus licencias.'
            $downloads = Join-Path $package 'Descargas'
            New-Item -ItemType Directory -Path $downloads -Force | Out-Null
            if (-not (Test-VcDependencies)) {
            $vc = Join-Path $downloads 'VisualCppRedist_AIO_x86_x64.exe'
            Get-VerifiedDownload 'https://github.com/abbodi1406/vcredist/releases/download/v0.106.0/VisualCppRedist_AIO_x86_x64.exe' $vc 'ED4F4FE7E72F3A03B1C377F145DDBB93CD52CC6672095EB4EB334EF3F98D6998'
            Write-Host 'Instalando los runtimes Visual C++ x86 y x64...'
            $process = Start-Process -FilePath $vc -ArgumentList '/y' -Wait -PassThru
            if ($process.ExitCode -eq 3010 -or $process.ExitCode -eq 1641) {
                throw 'Visual C++ requiere reiniciar Windows. Reinicia y vuelve a abrir Instalar_KryZal.exe.'
            }
            if ($process.ExitCode -ne 0) {
                throw ('Visual C++ termino con el codigo ' + $process.ExitCode + '. Revisa el registro y la guia manual.')
            }
            } else { Write-Host 'Visual C++ requerido ya instalado.' }
            $setup = Join-Path $downloads 'wampserver3.4.0_x64.exe'
            Get-VerifiedDownload 'https://wampserver.aviatechno.net/files/install/wampserver3.4.0_x64.exe' $setup '08BC458ACB5974E3F93857C234C4DAF72DA8A4DA842B736C1424D98B475E4FCD'
            Write-Host 'Instalando Wamp. Espera a que termine...'
            $process = Start-Process -FilePath $setup -ArgumentList '/SP- /SILENT /NORESTART /DIR="C:\wamp64"' -Wait -PassThru
            if ($process.ExitCode -eq 3010 -or $process.ExitCode -eq 1641) {
                throw 'Wamp requiere reiniciar Windows. Reinicia y vuelve a ejecutar el instalador.'
            }
            if ($process.ExitCode -ne 0 -or -not (Test-Path -LiteralPath (Join-Path $wamp 'wampmanager.exe'))) {
                throw ('Wamp no termino correctamente. Codigo: ' + $process.ExitCode + '. No se ha copiado el proyecto.')
            }
        }
    }
    $wampProcess = Get-Process -Name 'wampmanager' -ErrorAction SilentlyContinue
    if (-not $wampProcess) {
        Start-Process -FilePath (Join-Path $wamp 'wampmanager.exe') -WorkingDirectory $wamp
    }
    $php = Find-WampPhp $wamp
    $databaseTool = Join-Path $PSScriptRoot 'preparar_base.php'
    $destination = Install-KryzalFiles $source $wamp $php $databaseTool
    try { New-KryzalShortcut $wamp; Write-Host 'Acceso directo KryZal creado en el escritorio.' -ForegroundColor Green } catch { Write-Warning ('El proyecto esta instalado, pero no se pudo crear el acceso directo: ' + $_.Exception.Message) }
    Write-Host ''
    Write-Host ('Proyecto preparado en ' + $destination) -ForegroundColor Green
    Write-Host 'La base esta vacia y su estructura ya esta creada. En la web crearas el administrador.'
    Start-Process 'http://localhost/FKryzal/'
} catch {
    Write-Host ''
    Write-Host ('No se pudo completar: ' + $_.Exception.Message) -ForegroundColor Red
    Write-Host 'Consulta Documentacion\GUIA_WAMP.txt y el registro de instalacion.'
    if (-not $SinPausa) {
        Read-Host 'Pulsa ENTER para cerrar' | Out-Null
    }
    exit 1
} finally {
    if ($logging) {
        Stop-Transcript | Out-Null
    }
}
