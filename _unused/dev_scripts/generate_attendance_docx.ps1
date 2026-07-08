$ErrorActionPreference = "Stop"

$workspace = "C:\wamp_56\www\Office_management_system"
$inputMd = Join-Path $workspace "Attendance_Module_Implementation.md"
$outputDocx = Join-Path $workspace "Attendance_Module_Implementation.docx"
$tmpRoot = Join-Path $workspace ".tmp_docx_build"

if (Test-Path $tmpRoot) {
    Remove-Item -Recurse -Force $tmpRoot
}

New-Item -ItemType Directory -Path $tmpRoot | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tmpRoot "_rels") | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tmpRoot "word") | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tmpRoot "word\_rels") | Out-Null

function Escape-XmlText {
    param([string]$Text)
    if ($null -eq $Text) { return "" }
    $t = $Text.Replace("&", "&amp;").Replace("<", "&lt;").Replace(">", "&gt;")
    return $t
}

$mdLines = Get-Content -LiteralPath $inputMd
$paragraphs = New-Object System.Collections.Generic.List[string]

foreach ($line in $mdLines) {
    $txt = $line
    if ($txt -eq "") {
        $paragraphs.Add('<w:p/>')
        continue
    }

    $escaped = Escape-XmlText $txt
    $paragraphs.Add("<w:p><w:r><w:t xml:space=`"preserve`">$escaped</w:t></w:r></w:p>")
}

$documentXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"
    xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
    xmlns:o="urn:schemas-microsoft-com:office:office"
    xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
    xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"
    xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing"
    xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
    xmlns:w10="urn:schemas-microsoft-com:office:word"
    xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
    xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"
    xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"
    xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk"
    xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"
    xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"
    mc:Ignorable="w14 wp14">
  <w:body>
    $($paragraphs -join "`n    ")
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/>
      <w:cols w:space="708"/>
      <w:docGrid w:linePitch="360"/>
    </w:sectPr>
  </w:body>
</w:document>
"@

$contentTypesXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
"@

$relsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
"@

$wordRelsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>
"@

Set-Content -LiteralPath (Join-Path $tmpRoot "[Content_Types].xml") -Value $contentTypesXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmpRoot "_rels\.rels") -Value $relsXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmpRoot "word\document.xml") -Value $documentXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmpRoot "word\_rels\document.xml.rels") -Value $wordRelsXml -Encoding UTF8

$zipPath = Join-Path $workspace "Attendance_Module_Implementation.zip"
if (Test-Path $zipPath) { Remove-Item -Force $zipPath }
if (Test-Path $outputDocx) { Remove-Item -Force $outputDocx }

Compress-Archive -Path (Join-Path $tmpRoot "*") -DestinationPath $zipPath -Force
Rename-Item -Path $zipPath -NewName "Attendance_Module_Implementation.docx"

Remove-Item -Recurse -Force $tmpRoot
Write-Output "DOCX generated: $outputDocx"
