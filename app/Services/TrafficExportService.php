<?php

namespace App\Services;

use Illuminate\Support\Str;
use ZipArchive;

class TrafficExportService
{
    /**
     * Header columns for traffic spreadsheet.
     */
    public const COLUMNS = [
        ['title' => 'Date', 'width' => 22],
        ['title' => 'Domain', 'width' => 25],
        ['title' => 'Plan', 'width' => 16],
        ['title' => 'GEO', 'width' => 28],
        ['title' => 'BR', 'width' => 12],
        ['title' => 'Pages', 'width' => 12],
        ['title' => 'Time', 'width' => 12],
        ['title' => 'Referal traf', 'width' => 15],
        ['title' => 'Referal traf links', 'width' => 30],
        ['title' => 'Social traf', 'width' => 15],
        ['title' => 'Social traf links', 'width' => 30],
        ['title' => 'Organic traf', 'width' => 15],
        ['title' => 'Direct traf', 'width' => 15],
        ['title' => 'Keys', 'width' => 28],
        ['title' => 'Comment', 'width' => 35],
        ['title' => 'Status', 'width' => 14],
    ];

    /**
     * Generate an Excel (.xlsx) file for the given month and rows.
     *
     * @param  string  $month  Target month label (e.g. "October 2026")
     * @param  array  $rows  Matrix of spreadsheet rows
     * @param  string  $clientName  Client name for metadata
     * @return string Absolute path to the generated .xlsx file
     */
    public static function generateXlsx(string $month, array $rows, string $clientName = ''): string
    {
        $filename = sys_get_temp_dir().'/Traffic_Launch_'.Str::slug($month).'_'.uniqid().'.xlsx';

        $zip = new ZipArchive;
        if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Unable to create zip file at: {$filename}");
        }

        // 1. [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        // 4. xl/workbook.xml
        $sheetTitle = htmlspecialchars(mb_substr("Traffic {$month}", 0, 31), ENT_XML1, 'UTF-8');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="'.$sheetTitle.'" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

        // 5. xl/styles.xml (professional styling: Sky blue header, thin border, zebra rows, wrap text)
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><name val="Segoe UI"/><sz val="10.5"/><color rgb="FF1E293B"/></font>
    <font><b/><name val="Segoe UI"/><sz val="10.5"/><color rgb="FFFFFFFF"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF0284C7"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/></border>
    <border>
      <left style="thin"><color rgb="FFE2E8F0"/></left>
      <right style="thin"><color rgb="FFE2E8F0"/></right>
      <top style="thin"><color rgb="FFE2E8F0"/></top>
      <bottom style="thin"><color rgb="FFE2E8F0"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="3">
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment vertical="top" wrapText="1"/>
    </xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment vertical="top" wrapText="1"/>
    </xf>
  </cellXfs>
</styleSheet>');

        // 6. xl/worksheets/sheet1.xml
        $colsXml = '<cols>';
        foreach (self::COLUMNS as $idx => $col) {
            $colNum = $idx + 1;
            $colsXml .= '<col min="'.$colNum.'" max="'.$colNum.'" width="'.$col['width'].'" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $rowsXml = '<sheetData>';

        // Header Row
        $rowsXml .= '<row r="1" ht="28" customHeight="1">';
        foreach (self::COLUMNS as $idx => $col) {
            $ref = self::getColLetter($idx).'1';
            $val = htmlspecialchars($col['title'], ENT_XML1, 'UTF-8');
            $rowsXml .= '<c r="'.$ref.'" s="0" t="inlineStr"><is><t>'.$val.'</t></is></c>';
        }
        $rowsXml .= '</row>';

        // Data Rows
        $rNum = 2;
        foreach ($rows as $row) {
            $styleIdx = ($rNum % 2 === 0) ? '1' : '2';
            $rowsXml .= '<row r="'.$rNum.'">';

            foreach (self::COLUMNS as $cIdx => $col) {
                $ref = self::getColLetter($cIdx).$rNum;
                $cellVal = (string) ($row[$cIdx] ?? '');
                $escaped = htmlspecialchars($cellVal, ENT_XML1, 'UTF-8');
                $rowsXml .= '<c r="'.$ref.'" s="'.$styleIdx.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
            }

            $rowsXml .= '</row>';
            $rNum++;
        }

        $rowsXml .= '</sheetData>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  '.$colsXml.'
  '.$rowsXml.'
</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return $filename;
    }

    /**
     * Convert 0-indexed column integer to Excel column letter (e.g. 0 -> A, 15 -> P).
     */
    public static function getColLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex >= 0) {
            $letter = chr($colIndex % 26 + 65).$letter;
            $colIndex = intval($colIndex / 26) - 1;
        }

        return $letter;
    }
}
