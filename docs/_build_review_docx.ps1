Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$out = "c:\wamp64\www\Office_management_system\docs\whatsapp-meta-app-review.docx"
$dl = Join-Path $env:USERPROFILE "Downloads\whatsapp-meta-app-review.docx"
if (Test-Path $out) { Remove-Item $out -Force }

function X($s) {
  return [System.Security.SecurityElement]::Escape([string]$s)
}

$blocks = @(
  @{ s = "Heading1"; t = "WhatsApp Meta App Review APIs" },
  @{ s = "Normal"; t = "Change the setup values for the next client. The calls stay the same. Do not put the access token in this document." },
  @{ s = "Heading2"; t = "Setup" },
  @{ s = "Code"; t = "graph_version      v23.0" },
  @{ s = "Code"; t = "phone_number_id    1169968729541279" },
  @{ s = "Code"; t = "waba_id            1508742840538599" },
  @{ s = "Code"; t = "business_id        from me/businesses" },
  @{ s = "Code"; t = "dataset_id         from WABA dataset, only if events stay" },
  @{ s = "Code"; t = "to_number          your test number, digits only, no plus" },
  @{ s = "Code"; t = "template_name      hello_world" },
  @{ s = "Code"; t = "template_language  en_US" },
  @{ s = "Normal"; t = "Base URL: https://graph.facebook.com" },
  @{ s = "Normal"; t = "Header: Authorization: Bearer ACCESS_TOKEN" },
  @{ s = "Normal"; t = "Header: Content-Type: application/json" },
  @{ s = "Heading2"; t = "Remove these permissions" },
  @{ s = "Normal"; t = "email and manage_app_solution. This app does not call them." },
  @{ s = "Heading2"; t = "1. whatsapp_business_messaging" },
  @{ s = "Code"; t = "POST https://graph.facebook.com/v23.0/1169968729541279/messages" },
  @{ s = "Code"; t = '{"messaging_product":"whatsapp","to":"TO_NUMBER","type":"template","template":{"name":"hello_world","language":{"code":"en_US"}}}' },
  @{ s = "Heading2"; t = "2. whatsapp_business_management" },
  @{ s = "Code"; t = "GET https://graph.facebook.com/v23.0/1508742840538599/message_templates?fields=name,status,language&limit=5" },
  @{ s = "Code"; t = "GET https://graph.facebook.com/v23.0/1508742840538599/phone_numbers?fields=id,display_phone_number,verified_name" },
  @{ s = "Code"; t = "GET https://graph.facebook.com/v23.0/1169968729541279?fields=id,display_phone_number,verified_name,whatsapp_business_account" },
  @{ s = "Heading2"; t = "3. public_profile" },
  @{ s = "Code"; t = "GET https://graph.facebook.com/v23.0/me?fields=id,name" },
  @{ s = "Heading2"; t = "4. business_management" },
  @{ s = "Code"; t = "GET https://graph.facebook.com/v23.0/me/businesses?fields=id,name" },
  @{ s = "Code"; t = "GET https://graph.facebook.com/v23.0/me/assigned_whatsapp_business_accounts?fields=id,name" },
  @{ s = "Code"; t = "GET https://graph.facebook.com/v23.0/BUSINESS_ID/owned_whatsapp_business_accounts?fields=id,name" },
  @{ s = "Heading2"; t = "5. whatsapp_business_manage_events (optional)" },
  @{ s = "Normal"; t = "Run only if this permission stays on the use case. If the dataset id is missing, remove the permission." },
  @{ s = "Code"; t = "GET https://graph.facebook.com/v23.0/1508742840538599/dataset?fields=id,name" },
  @{ s = "Code"; t = "POST https://graph.facebook.com/v23.0/DATASET_ID/events" },
  @{ s = "Code"; t = '{"data":[{"event_name":"LeadSubmitted","event_time":0,"action_source":"business_messaging","messaging_channel":"whatsapp","user_data":{"ph":["TO_NUMBER"]}}]}' },
  @{ s = "Normal"; t = "Set event_time to the current Unix time before you submit." },
  @{ s = "Heading2"; t = "After the calls" },
  @{ s = "Normal"; t = "Wait 15 to 30 minutes. Then submit only whatsapp_business_messaging, whatsapp_business_management, business_management, and public_profile. Business Verification must be complete before the app can go Live." }
)

$paras = ""
foreach ($b in $blocks) {
  $style = $b.s
  $paras += "<w:p><w:pPr><w:pStyle w:val=`"$style`"/></w:pPr><w:r><w:t xml:space=`"preserve`">$(X $b.t)</w:t></w:r></w:p>"
}

$document = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    $paras
    <w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/></w:sectPr>
  </w:body>
</w:document>
"@

$styles = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/></w:rPr></w:style>
  <w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:sz w:val="32"/><w:szCs w:val="32"/></w:rPr></w:style>
  <w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:rPr><w:b/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr></w:style>
  <w:style w:type="paragraph" w:styleId="Code"><w:name w:val="Code"/><w:basedOn w:val="Normal"/><w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr></w:style>
</w:styles>
"@

$contentTypes = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>
"@

$rels = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
"@

$docRels = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
"@

$utf8 = New-Object System.Text.UTF8Encoding $false
$zip = [System.IO.Compression.ZipFile]::Open($out, [System.IO.Compression.ZipArchiveMode]::Create)
function Add-Entry($name, $text) {
  $entry = $zip.CreateEntry($name, [System.IO.Compression.CompressionLevel]::Optimal)
  $stream = $entry.Open()
  $bytes = $utf8.GetBytes($text)
  $stream.Write($bytes, 0, $bytes.Length)
  $stream.Close()
}
Add-Entry "[Content_Types].xml" $contentTypes
Add-Entry "_rels/.rels" $rels
Add-Entry "word/document.xml" $document
Add-Entry "word/styles.xml" $styles
Add-Entry "word/_rels/document.xml.rels" $docRels
$zip.Dispose()
Copy-Item $out $dl -Force
Write-Output "OK $($(Get-Item $out).Length)"
