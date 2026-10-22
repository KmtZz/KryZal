$ErrorActionPreference = 'Stop'
$wamp = Split-Path -Parent $PSScriptRoot
try {
    if (-not (Get-Process -Name 'wampmanager' -ErrorAction SilentlyContinue)) {
        Start-Process -FilePath (Join-Path $wamp 'wampmanager.exe') -WorkingDirectory $wamp
    }
    $ready = $false
    for ($attempt=0; $attempt -lt 30; $attempt++) {
        try {
            $response = Invoke-WebRequest -Uri 'http://localhost/FKryzal/' -Method Head -UseBasicParsing -TimeoutSec 2
            if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 400) {
                $ready = $true; break
            }
        } catch {}
        Start-Sleep -Seconds 2
    }
    if (-not $ready) {
        throw 'La web no responde. Comprueba que el icono de Wamp este verde y vuelve a abrir KryZal.'
    }
    Start-Process 'http://localhost/FKryzal/'
} catch {
    Add-Type -AssemblyName System.Windows.Forms
    [System.Windows.Forms.MessageBox]::Show($_.Exception.Message, 'KryZal', 'OK', 'Warning') | Out-Null
}
