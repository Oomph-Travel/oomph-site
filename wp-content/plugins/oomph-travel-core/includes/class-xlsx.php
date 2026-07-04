<?php
/**
 * Minimal .xlsx -> .csv converter (no external libraries).
 *
 * Reads one named sheet from an Excel workbook and writes a CSV. Resolves
 * shared strings and inline strings, and converts Excel date serials to ISO
 * (Y-m-d) by reading the cell number formats — so the "All Sailings" export
 * can be uploaded as-is without anyone hand-saving it to CSV.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class XLSX {

	/** Built-in number-format ids that mean "date/time". */
	private const DATE_FMT_IDS = array( 14, 15, 16, 17, 18, 19, 20, 21, 22, 27, 30, 36, 45, 46, 47, 50, 57, 58 );

	/**
	 * Convert one sheet of an .xlsx to a CSV file.
	 *
	 * @param string $xlsx_path  Path to the .xlsx.
	 * @param string $sheet_name Sheet to export (e.g. "All Sailings").
	 * @param string $csv_path   Where to write the CSV.
	 * @return string|\WP_Error   The CSV path on success.
	 */
	public static function to_csv( string $xlsx_path, string $sheet_name, string $csv_path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error( 'no_zip', 'The server is missing PHP ZipArchive, which is needed to read Excel files.' );
		}
		@ini_set( 'memory_limit', '512M' ); // phpcs:ignore WordPress.PHP.IniSet -- large sheets.

		$zip = new \ZipArchive();
		if ( true !== $zip->open( $xlsx_path ) ) {
			return new \WP_Error( 'bad_zip', 'That file could not be opened as an Excel workbook.' );
		}

		$wb   = self::dom( $zip->getFromName( 'xl/workbook.xml' ) );
		$rels = self::dom( $zip->getFromName( 'xl/_rels/workbook.xml.rels' ) );
		if ( ! $wb || ! $rels ) {
			$zip->close();
			return new \WP_Error( 'bad_xlsx', 'The Excel file is missing expected internal parts.' );
		}

		// Map relationship id -> worksheet target.
		$rid_target = array();
		foreach ( $rels->getElementsByTagName( 'Relationship' ) as $r ) {
			$rid_target[ $r->getAttribute( 'Id' ) ] = $r->getAttribute( 'Target' );
		}
		$rel_ns     = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
		$sheet_path = '';
		$available  = array();
		foreach ( $wb->getElementsByTagName( 'sheet' ) as $s ) {
			$available[] = $s->getAttribute( 'name' );
			if ( $s->getAttribute( 'name' ) === $sheet_name ) {
				$rid = $s->getAttributeNS( $rel_ns, 'id' );
				if ( '' === $rid && $s->hasAttribute( 'r:id' ) ) {
					$rid = $s->getAttribute( 'r:id' );
				}
				$tgt = $rid_target[ $rid ] ?? '';
				if ( '' !== $tgt ) {
					$sheet_path = ( 0 === strpos( $tgt, '/' ) ) ? ltrim( $tgt, '/' ) : 'xl/' . $tgt;
				}
				break;
			}
		}
		if ( '' === $sheet_path ) {
			$zip->close();
			return new \WP_Error( 'no_sheet', sprintf( 'Sheet "%s" not found. Sheets in this file: %s', $sheet_name, implode( ', ', $available ) ) );
		}

		// Shared strings.
		$shared = array();
		$sst    = self::dom( $zip->getFromName( 'xl/sharedStrings.xml' ) );
		if ( $sst ) {
			foreach ( $sst->getElementsByTagName( 'si' ) as $si ) {
				$text = '';
				foreach ( $si->getElementsByTagName( 't' ) as $t ) {
					$text .= $t->textContent;
				}
				$shared[] = $text;
			}
		}

		// Styles -> which cell-style indexes are dates.
		$cellxf_numfmt = array();
		$custom_date   = array();
		$styles        = self::dom( $zip->getFromName( 'xl/styles.xml' ) );
		if ( $styles ) {
			foreach ( $styles->getElementsByTagName( 'numFmt' ) as $nf ) {
				$code = strtolower( $nf->getAttribute( 'formatCode' ) );
				$looks_date = preg_match( '/(yy|dd|mmm)/', $code )
					|| ( false !== strpos( $code, 'm' ) && ( false !== strpos( $code, '/' ) || false !== strpos( $code, '-' ) ) );
				if ( $looks_date && false === strpos( $code, '%' ) && false === strpos( $code, '$' ) ) {
					$custom_date[ (int) $nf->getAttribute( 'numFmtId' ) ] = true;
				}
			}
			foreach ( $styles->getElementsByTagName( 'cellXfs' ) as $cxfs ) {
				foreach ( $cxfs->getElementsByTagName( 'xf' ) as $xf ) {
					$cellxf_numfmt[] = (int) ( '' !== $xf->getAttribute( 'numFmtId' ) ? $xf->getAttribute( 'numFmtId' ) : 0 );
				}
				break;
			}
		}
		$is_date_style = static function ( $s_idx ) use ( $cellxf_numfmt, $custom_date ): bool {
			if ( '' === $s_idx ) {
				return false;
			}
			$nf = $cellxf_numfmt[ (int) $s_idx ] ?? -1;
			return in_array( $nf, self::DATE_FMT_IDS, true ) || isset( $custom_date[ $nf ] );
		};

		$sheet = self::dom( $zip->getFromName( $sheet_path ) );
		$zip->close();
		if ( ! $sheet ) {
			return new \WP_Error( 'no_sheet_xml', 'The worksheet could not be read.' );
		}

		$fh = fopen( $csv_path, 'w' );
		if ( false === $fh ) {
			return new \WP_Error( 'no_write', 'Could not write the temporary CSV file.' );
		}

		$rows_out = array();
		$maxcol   = 0;
		foreach ( $sheet->getElementsByTagName( 'row' ) as $row ) {
			$cells = array();
			foreach ( $row->getElementsByTagName( 'c' ) as $c ) {
				$ci     = self::col_index( $c->getAttribute( 'r' ) );
				$maxcol = max( $maxcol, $ci );
				$t      = $c->getAttribute( 't' );
				$s_idx  = $c->getAttribute( 's' );
				$val    = '';
				if ( 's' === $t ) {
					$v   = $c->getElementsByTagName( 'v' )->item( 0 );
					$val = ( null !== $v ) ? ( $shared[ (int) $v->textContent ] ?? '' ) : '';
				} elseif ( 'inlineStr' === $t ) {
					foreach ( $c->getElementsByTagName( 't' ) as $tn ) {
						$val .= $tn->textContent;
					}
				} elseif ( 'str' === $t ) {
					$v   = $c->getElementsByTagName( 'v' )->item( 0 );
					$val = ( null !== $v ) ? $v->textContent : '';
				} else {
					$v = $c->getElementsByTagName( 'v' )->item( 0 );
					if ( null !== $v ) {
						$raw = $v->textContent;
						$val = ( $is_date_style( $s_idx ) && is_numeric( $raw ) ) ? self::serial_to_iso( (float) $raw ) : $raw;
					}
				}
				$cells[ $ci ] = $val;
			}
			$rows_out[] = $cells;
		}

		foreach ( $rows_out as $cells ) {
			$line = array();
			for ( $i = 0; $i <= $maxcol; $i++ ) {
				$line[] = $cells[ $i ] ?? '';
			}
			fputcsv( $fh, $line );
		}
		fclose( $fh );

		return $csv_path;
	}

	private static function dom( $xml ): ?\DOMDocument {
		if ( ! is_string( $xml ) || '' === $xml ) {
			return null;
		}
		$prev = libxml_use_internal_errors( true );
		$d    = new \DOMDocument();
		$ok   = $d->loadXML( $xml );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );
		return $ok ? $d : null;
	}

	/** "AB12" -> zero-based column index. */
	private static function col_index( string $ref ): int {
		if ( ! preg_match( '/^[A-Z]+/', $ref, $m ) ) {
			return 0;
		}
		$n = 0;
		foreach ( str_split( $m[0] ) as $ch ) {
			$n = $n * 26 + ( ord( $ch ) - 64 );
		}
		return $n - 1;
	}

	/** Excel 1900 serial -> Y-m-d (25569 = 1899-12-30 epoch to Unix epoch). */
	private static function serial_to_iso( float $serial ): string {
		return gmdate( 'Y-m-d', (int) round( ( $serial - 25569 ) * 86400 ) );
	}
}
