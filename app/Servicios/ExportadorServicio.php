<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Core\Respuesta;

/**
 * Exportación de reportes a CSV (compatible con Excel) y a XLSX real.
 * El XLSX se arma a mano (Office Open XML dentro de un ZIP) para no depender
 * de la extensión zip de PHP, que no viene activa en todos los XAMPP.
 */
final class ExportadorServicio
{
    public static function csv(string $nombre, array $cabeceras, array $filas): Respuesta
    {
        $f = fopen('php://temp', 'r+');
        fwrite($f, "\xEF\xBB\xBF"); // BOM para que Excel reconozca UTF-8
        fputcsv($f, $cabeceras, ';');
        foreach ($filas as $fila) {
            fputcsv($f, array_map(fn ($v) => is_float($v) ? number_format($v, 2, ',', '') : $v, $fila), ';');
        }
        rewind($f);
        $contenido = stream_get_contents($f);
        fclose($f);
        return Respuesta::descarga((string) $contenido, self::archivo($nombre, 'csv'), 'text/csv; charset=UTF-8');
    }

    public static function xlsx(string $nombre, string $titulo, array $cabeceras, array $filas, array $resumen = []): Respuesta
    {
        return Respuesta::descarga(
            self::generarXlsx($titulo, $cabeceras, $filas, $resumen),
            self::archivo($nombre, 'xlsx'),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    private static function archivo(string $nombre, string $ext): string
    {
        return 'hotel-elegance_' . preg_replace('/[^a-z0-9_-]/i', '-', $nombre) . '_' . date('Ymd-His') . '.' . $ext;
    }

    /** Genera el binario XLSX con una hoja, título, cabecera con estilo y filas. */
    public static function generarXlsx(string $titulo, array $cabeceras, array $filas, array $resumen = []): string
    {
        $xml = fn ($t) => htmlspecialchars((string) $t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $columna = function (int $i): string {
            $s = '';
            for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
                $s = chr(65 + ($i - 1) % 26) . $s;
            }
            return $s;
        };
        $celda = function (string $ref, mixed $v, int $estilo = 0) use ($xml): string {
            if (is_int($v) || is_float($v)) {
                return '<c r="' . $ref . '" s="' . ($estilo ?: (is_float($v) ? 3 : 0)) . '"><v>' . $v . '</v></c>';
            }
            return '<c r="' . $ref . '" t="inlineStr" s="' . $estilo . '"><is><t xml:space="preserve">' . $xml($v) . '</t></is></c>';
        };

        $filasXml = [];
        $n = 1;
        $filasXml[] = '<row r="1" ht="26" customHeight="1">' . $celda('A1', $titulo, 4) . '</row>';
        $filasXml[] = '<row r="2">' . $celda('A2', 'Hotel Elegance · Generado el ' . date('d/m/Y H:i'), 5) . '</row>';
        $n = 3;
        foreach ($resumen as $etiqueta => $valor) {
            $n++;
            $filasXml[] = '<row r="' . $n . '">' . $celda('A' . $n, $etiqueta, 2) . $celda('B' . $n, $valor) . '</row>';
        }
        $n += 2;
        $inicioTabla = $n;
        $fila = '';
        foreach ($cabeceras as $i => $c) {
            $fila .= $celda($columna($i) . $n, $c, 1);
        }
        $filasXml[] = '<row r="' . $n . '">' . $fila . '</row>';
        foreach ($filas as $datos) {
            $n++;
            $fila = '';
            foreach (array_values($datos) as $i => $v) {
                $fila .= $celda($columna($i) . $n, $v);
            }
            $filasXml[] = '<row r="' . $n . '">' . $fila . '</row>';
        }
        $anchos = '';
        foreach ($cabeceras as $i => $c) {
            $max = mb_strlen((string) $c);
            foreach (array_slice($filas, 0, 300) as $d) {
                $max = max($max, mb_strlen((string) (array_values($d)[$i] ?? '')));
            }
            $anchos .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . min(60, max(10, $max + 3)) . '" customWidth="1"/>';
        }
        $ultima = $columna(max(0, count($cabeceras) - 1));
        $hoja = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $inicioTabla . '" topLeftCell="A' . ($inicioTabla + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $anchos . '</cols><sheetData>' . implode('', $filasXml) . '</sheetData>'
            . ($filas ? '<autoFilter ref="A' . $inicioTabla . ':' . $ultima . $n . '"/>' : '')
            . '</worksheet>';

        $estilos = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
            . '<fonts count="4"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="16"/><color rgb="FF0F1B2D"/><name val="Cambria"/></font><font><i/><sz val="9"/><color rgb="FF8D93A0"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0F1B2D"/></patternFill></fill></fills>'
            . '<borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="6"><xf/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" applyNumberFormat="1"/><xf fontId="2" applyFont="1"/><xf fontId="3" applyFont="1"/></cellXfs></styleSheet>';

        $archivos = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets>' . ($filas ? '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">Reporte!$A$' . $inicioTabla . ':$' . $ultima . '$' . $n . '</definedName></definedNames>' : '') . '</workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => $hoja,
            'xl/styles.xml' => $estilos,
        ];
        return self::zip($archivos);
    }

    /** ZIP mínimo (método «stored», sin compresión) con CRC-32. */
    private static function zip(array $archivos): string
    {
        $datos = '';
        $central = '';
        $desplazamiento = 0;
        $hora = (int) ((date('H') << 11) | (date('i') << 5) | (int) (date('s') / 2));
        $fecha = (int) (((date('Y') - 1980) << 9) | (date('n') << 5) | date('j'));
        foreach ($archivos as $nombre => $contenido) {
            $crc = crc32($contenido);
            $tam = strlen($contenido);
            $cabecera = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $hora, $fecha, $crc, $tam, $tam, strlen($nombre), 0) . $nombre;
            $datos .= $cabecera . $contenido;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, $hora, $fecha, $crc, $tam, $tam, strlen($nombre), 0, 0, 0, 0, 0, $desplazamiento) . $nombre;
            $desplazamiento += strlen($cabecera) + $tam;
        }
        return $datos . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, count($archivos), count($archivos), strlen($central), $desplazamiento, 0);
    }
}
