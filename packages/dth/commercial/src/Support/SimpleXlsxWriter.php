<?php

namespace Dth\Commercial\Support;

final class SimpleXlsxWriter
{
    public function __construct(private readonly SimpleZipArchive $zip = new SimpleZipArchive()) {}

    /** @param array<string, array<int, array<int, mixed>>> $sheets */
    public function build(array $sheets): string
    {
        if ($sheets === []) {
            $sheets = ['Report' => [['No data']]];
        }

        $normalized = [];
        $used = [];
        foreach ($sheets as $title => $rows) {
            $sheetTitle = $this->sheetTitle((string) $title, $used);
            $used[] = $sheetTitle;
            $normalized[$sheetTitle] = $rows;
        }

        $files = [
            '[Content_Types].xml' => $this->contentTypes(count($normalized)),
            '_rels/.rels' => $this->rootRelationships(),
            'docProps/core.xml' => $this->coreProperties(),
            'docProps/app.xml' => $this->appProperties(array_keys($normalized)),
            'xl/workbook.xml' => $this->workbook(array_keys($normalized)),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelationships(count($normalized)),
            'xl/styles.xml' => $this->styles(),
        ];

        $index = 1;
        foreach ($normalized as $rows) {
            $files['xl/worksheets/sheet'.$index.'.xml'] = $this->worksheet($rows);
            $index++;
        }

        return $this->zip->build($files);
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function worksheet(array $rows): string
    {
        $xmlRows = [];
        foreach (array_values($rows) as $rowIndex => $row) {
            $cells = [];
            foreach (array_values($row) as $columnIndex => $value) {
                $reference = $this->columnName($columnIndex + 1).($rowIndex + 1);
                $isHeader = $rowIndex === 0;
                $style = $isHeader ? ' s="1"' : '';

                if ($value === null || $value === '') {
                    $cells[] = '<c r="'.$reference.'"'.$style.'/>';
                    continue;
                }

                if (is_int($value) || is_float($value)) {
                    $cells[] = '<c r="'.$reference.'"'.$style.'><v>'.$value.'</v></c>';
                    continue;
                }

                if (is_bool($value)) {
                    $cells[] = '<c r="'.$reference.'" t="b"'.$style.'><v>'.($value ? '1' : '0').'</v></c>';
                    continue;
                }

                $text = $this->xml((string) $value);
                $cells[] = '<c r="'.$reference.'" t="inlineStr"'.$style.'><is><t xml:space="preserve">'.$text.'</t></is></c>';
            }

            $xmlRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<sheetData>'.implode('', $xmlRows).'</sheetData>'
            .'</worksheet>';
    }

    private function contentTypes(int $sheetCount): string
    {
        $sheets = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$sheets
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    /** @param array<int, string> $titles */
    private function workbook(array $titles): string
    {
        $sheets = '';
        foreach ($titles as $index => $title) {
            $sheets .= '<sheet name="'.$this->xml($title).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheets.'</sheets></workbook>';
    }

    private function workbookRelationships(int $sheetCount): string
    {
        $rels = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $rels .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }
        $rels .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$rels.'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'<dxfs count="0"/><tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>'
            .'</styleSheet>';
    }

    private function coreProperties(): string
    {
        $now = gmdate('Y-m-d\\TH:i:s\\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>DTH Commercial</dc:creator><cp:lastModifiedBy>DTH Commercial</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    /** @param array<int, string> $titles */
    private function appProperties(array $titles): string
    {
        $parts = implode('', array_map(fn (string $title): string => '<vt:lpstr>'.$this->xml($title).'</vt:lpstr>', $titles));

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>DTH Commercial</Application><Sheets>'.count($titles).'</Sheets>'
            .'<TitlesOfParts><vt:vector size="'.count($titles).'" baseType="lpstr">'.$parts.'</vt:vector></TitlesOfParts>'
            .'</Properties>';
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    /** @param array<int, string> $used */
    private function sheetTitle(string $title, array $used): string
    {
        $title = preg_replace('~[\\/?:*\[\]]~u', ' ', trim($title)) ?: 'Report';
        $title = $this->truncate($title, 31);
        $candidate = $title !== '' ? $title : 'Report';
        $suffix = 2;
        while (in_array($candidate, $used, true)) {
            $tail = ' '.$suffix;
            $candidate = $this->truncate($title, 31 - $this->length($tail)).$tail;
            $suffix++;
        }

        return $candidate;
    }

    private function truncate(string $value, int $length): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, 0, $length)
            : substr($value, 0, $length);
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
