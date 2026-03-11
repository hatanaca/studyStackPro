# StudyTrackPro Security Quick-Fix Script
# Run in backend directory

Write-Host "Fixing CORS..." -ForegroundColor Green
$corsFile = 'config\cors.php'
$corsContent = Get-Content $corsFile -Raw
$corsContent = $corsContent -replace "\['\*'\]", "[]"
Set-Content -Path $corsFile -Value $corsContent -Encoding UTF8

Write-Host "Fixing Sanctum..." -ForegroundColor Green
$sanctumFile = 'config\sanctum.php'
$sanctumContent = Get-Content $sanctumFile -Raw
$sanctumContent = $sanctumContent -replace "'expiration' => null,", "'expiration' => 1440,"
Set-Content -Path $sanctumFile -Value $sanctumContent -Encoding UTF8

Write-Host "All security fixes applied!" -ForegroundColor Green
