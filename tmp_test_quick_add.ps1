$base = "http://localhost/Office_management_system"
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

# GET login page for CSRF cookie
$loginPage = Invoke-WebRequest -Uri "$base/auth/login" -SessionVariable session -UseBasicParsing
$csrfMatch = [regex]::Match($loginPage.Content, 'name="ci_csrf_token"\s+value="([^"]+)"')
$csrf = if ($csrfMatch.Success) { $csrfMatch.Groups[1].Value } else { "" }

# POST login (adjust credentials if needed)
$body = @{
    email = "sateri.mangesh@gmail.com"
    password = "password"
    ci_csrf_token = $csrf
}
try {
    $login = Invoke-WebRequest -Uri "$base/auth/login" -Method POST -Body $body -WebSession $session -UseBasicParsing -MaximumRedirection 5
    Write-Host "Login status:" $login.StatusCode
} catch {
    Write-Host "Login failed:" $_.Exception.Message
}

$qa = Invoke-WebRequest -Uri "$base/my-works/quick-add" -WebSession $session -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue
Write-Host "Quick-add status:" $qa.StatusCode
if ($qa.Headers.Location) { Write-Host "Redirect:" $qa.Headers.Location }
if ($qa.Content -match "Access Denied") { Write-Host "BODY: Access Denied page" }
if ($qa.Content -match "Quick add work item") { Write-Host "BODY: Quick add page OK" }
