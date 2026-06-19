<?php
defined( 'ABSPATH' ) || exit;

/**
 * Build attachment file(s) for the run. xlsx => one workbook (sheet per form);
 * csv => one file per form (returns multiple paths).
 *
 * @return string[] Temp file paths.
 */
function dsagfe_build_attachments( string $format, array $sections ): array {
	$tmp = trailingslashit( sys_get_temp_dir() );

	if ( 'csv' === $format ) {
		$files = [];
		foreach ( $sections as $i => $sec ) {
			if ( $sec['count'] < 1 ) {
				continue;
			}
			[ $headers, $rows ] = dsagfe_section_table( $sec );
			$base = sanitize_file_name( 'entry-digest-' . dsagfe_slug( $sec['form']['title'] ) . '-' . gmdate( 'Y-m-d' ) . '.csv' );
			$path = $tmp . $i . '-' . $base;
			if ( dsagfe_write_csv( $path, $headers, $rows ) ) {
				$files[] = $path;
			}
		}
		return $files;
	}

	// xlsx - one sheet per form with data.
	$sheets    = [];
	$used_names = [];
	foreach ( $sections as $sec ) {
		if ( $sec['count'] < 1 ) {
			continue;
		}
		[ $headers, $rows ] = dsagfe_section_table( $sec );
		$name = dsagfe_unique_sheet_name( $sec['form']['title'], $used_names );
		$sheets[] = [ 'name' => $name, 'headers' => $headers, 'rows' => $rows ];
	}
	if ( empty( $sheets ) ) {
		return [];
	}
	$path = $tmp . sanitize_file_name( 'entry-digest-' . gmdate( 'Y-m-d' ) . '.xlsx' );
	return dsagfe_write_xlsx( $path, $sheets ) ? [ $path ] : [];
}

/**
 * Build [ headers, rows ] for a section's full data export.
 */
function dsagfe_section_table( array $sec ): array {
	$field_map = $sec['field_map'];
	$headers   = array_merge( [
		__( 'Entry ID', 'entry-digest-for-gravity-forms' ),
		__( 'Date Submitted (UTC)', 'entry-digest-for-gravity-forms' ),
		__( 'IP Address', 'entry-digest-for-gravity-forms' ),
	], array_values( $field_map ) );
	$rows      = [];
	foreach ( $sec['entries'] as $entry ) {
		$row = [ $entry['id'] ?? '', $entry['date_created'] ?? '', $entry['ip'] ?? '' ];
		foreach ( array_keys( $field_map ) as $fid ) {
			$row[] = $entry[ $fid ] ?? '';
		}
		$rows[] = $row;
	}
	return [ $headers, $rows ];
}

function dsagfe_slug( string $s ): string {
	$s = sanitize_title( $s );
	return $s ?: 'form';
}

/**
 * A valid, unique (<=31 char) Excel sheet name.
 */
function dsagfe_unique_sheet_name( string $title, array &$used ): string {
	$name = preg_replace( '/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $title );
	$name = trim( mb_substr( $name, 0, 28 ) );
	if ( '' === $name ) {
		$name = 'Sheet';
	}
	$base = $name;
	$n    = 1;
	while ( in_array( strtolower( $name ), array_map( 'strtolower', $used ), true ) ) {
		$name = mb_substr( $base, 0, 25 ) . ' (' . ( ++$n ) . ')';
	}
	$used[] = $name;
	return $name;
}
// ════════════════════════════════════════════════════════════════
//  CSV writer
// ════════════════════════════════════════════════════════════════
/**
 * Encode one CSV row per RFC-4180 (double-quote escaping).
 *
 * @param string[] $fields
 */
function dsagfe_csv_line( array $fields ): string {
	$cells = [];
	foreach ( $fields as $field ) {
		$field = (string) $field;
		if ( str_contains( $field, ',' ) || str_contains( $field, '"' ) || str_contains( $field, "\n" ) || str_contains( $field, "\r" ) ) {
			$field = '"' . str_replace( '"', '""', $field ) . '"';
		}
		$cells[] = $field;
	}
	return implode( ',', $cells ) . "\r\n";
}

function dsagfe_write_csv( string $filepath, array $headers, array $rows ): bool {
	global $wp_filesystem;
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( empty( $wp_filesystem ) ) {
		WP_Filesystem();
	}
	if ( ! is_object( $wp_filesystem ) ) {
		return false;
	}

	$content  = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel.
	$content .= dsagfe_csv_line( $headers );
	foreach ( $rows as $row ) {
		$content .= dsagfe_csv_line( array_map( 'strval', $row ) );
	}

	return (bool) $wp_filesystem->put_contents( $filepath, $content, FS_CHMOD_FILE );
}

// ════════════════════════════════════════════════════════════════
//  Minimal .xlsx writer - pure PHP, multi-sheet, no Composer deps
// ════════════════════════════════════════════════════════════════
/**
 * @param string $filepath
 * @param array  $sheets  [ [ 'name' => str, 'headers' => [], 'rows' => [[]] ], ... ]
 */
function dsagfe_write_xlsx( string $filepath, array $sheets ): bool {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return false;
	}
	if ( empty( $sheets ) ) {
		return false;
	}

	$strings      = [];
	$string_index = [];
	$si = static function ( $value ) use ( &$strings, &$string_index ): int {
		$v = (string) $value;
		if ( ! isset( $string_index[ $v ] ) ) {
			$string_index[ $v ] = count( $strings );
			$strings[]          = $v;
		}
		return $string_index[ $v ];
	};

	$sheet_parts = []; // index => worksheet xml
	foreach ( $sheets as $sidx => $sheet ) {
		$headers   = $sheet['headers'];
		$rows      = $sheet['rows'];
		$col_count = count( $headers );
		$xml       = '';
		$row_num   = 1;

		$xml .= '<row r="' . $row_num . '">';
		foreach ( $headers as $ci => $val ) {
			$xml .= '<c r="' . dsagfe_col_letter( $ci ) . $row_num . '" t="s" s="1"><v>' . $si( $val ) . '</v></c>';
		}
		$xml .= '</row>';
		$row_num++;

		foreach ( $rows as $row ) {
			$xml .= '<row r="' . $row_num . '">';
			foreach ( $row as $ci => $val ) {
				if ( $ci >= $col_count ) {
					break;
				}
				$xml .= '<c r="' . dsagfe_col_letter( $ci ) . $row_num . '" t="s"><v>' . $si( $val ) . '</v></c>';
			}
			$xml .= '</row>';
			$row_num++;
		}

		$last_col = dsagfe_col_letter( max( 0, $col_count - 1 ) );
		$last_row = max( 1, $row_num - 1 );

		$sheet_parts[ $sidx ] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <dimension ref="A1:' . $last_col . $last_row . '"/>
  <sheetData>' . $xml . '</sheetData>
</worksheet>';
	}

	$ss_items = '';
	foreach ( $strings as $s ) {
		$ss_items .= '<si><t xml:space="preserve">'
		           . htmlspecialchars( $s, ENT_XML1 | ENT_QUOTES, 'UTF-8' )
		           . '</t></si>';
	}

	$n = count( $sheets );

	// [Content_Types].xml - one Override per worksheet.
	$ct_overrides = '';
	for ( $i = 1; $i <= $n; $i++ ) {
		$ct_overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
	}

	// workbook.xml <sheets> + rels. Sheets take rId1..rIdN; shared strings/styles follow.
	$wb_sheets = '';
	$wb_rels   = '';
	for ( $i = 1; $i <= $n; $i++ ) {
		$nm         = htmlspecialchars( $sheets[ $i - 1 ]['name'], ENT_XML1 | ENT_QUOTES, 'UTF-8' );
		$wb_sheets .= '<sheet name="' . $nm . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
		$wb_rels   .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
	}
	$rid_ss     = $n + 1;
	$rid_styles = $n + 2;
	$wb_rels   .= '<Relationship Id="rId' . $rid_ss . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
	$wb_rels   .= '<Relationship Id="rId' . $rid_styles . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

	$files = [
		'[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  ' . $ct_overrides . '
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>',
		'_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>',
		'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>' . $wb_sheets . '</sheets>
</workbook>',
		'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $wb_rels . '</Relationships>',
		'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="11"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><name val="Calibri"/></font>
  </fonts>
  <fills count="2">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
  </fills>
  <borders count="1">
    <border><left/><right/><top/><bottom/><diagonal/></border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
  </cellXfs>
</styleSheet>',
		'xl/sharedStrings.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count( $strings ) . '" uniqueCount="' . count( $strings ) . '">' . $ss_items . '</sst>',
	];

	for ( $i = 1; $i <= $n; $i++ ) {
		$files[ 'xl/worksheets/sheet' . $i . '.xml' ] = $sheet_parts[ $i - 1 ];
	}

	$zip = new ZipArchive();
	if ( true !== $zip->open( $filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		return false;
	}
	foreach ( $files as $name => $content ) {
		$zip->addFromString( $name, $content );
	}
	$zip->close();

	return file_exists( $filepath );
}

/**
 * Convert a 0-based column index to an Excel column letter (A, B, …, Z, AA, …).
 */
function dsagfe_col_letter( int $index ): string {
	$letter = '';
	$n      = $index + 1;
	while ( $n > 0 ) {
		$mod    = ( $n - 1 ) % 26;
		$letter = chr( 65 + $mod ) . $letter;
		$n      = (int) ( ( $n - $mod - 1 ) / 26 );
	}
	return $letter;
}
