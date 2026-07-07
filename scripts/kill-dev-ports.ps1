$ports = 8000, 8080, 3000, 3001, 3002

foreach ($port in $ports) {
    $connections = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
    foreach ($ownerPid in ($connections | Select-Object -ExpandProperty OwningProcess -Unique)) {
        try {
            $proc = Get-Process -Id $ownerPid -ErrorAction Stop
            Stop-Process -Id $ownerPid -Force -ErrorAction Stop
            Write-Host "Killed $($proc.ProcessName) (PID $ownerPid) on port $port"
        } catch {
        }
    }
}
