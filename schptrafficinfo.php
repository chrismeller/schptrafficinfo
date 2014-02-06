<?php

	namespace SCHPTrafficInfo;
	
	class SCHPTrafficInfo {
		
		const BASE_URL = 'http://www.scdps.gov/schp/schpwebcad/Default.aspx';
		
		public function get_incidents ( ) {
			
			$contents = $this->get_page();
			
			$dom = new \DOMDocument( '1.0', 'utf-8' );
			@$dom->loadHTML( $contents );
			
			$xpath = new \DOMXPath( $dom );
			
			$rows = $xpath->query( '//table[ @id="DataGrid1" ]//tr[ position() > 1 ]' );
			
			$incidents = array();
			foreach ( $rows as $row ) {
				
				$tds = $xpath->query( './td', $row );
				
				$incidents[] = array(
					'type' => trim( $tds->item(0)->nodeValue ),
					'status' => trim( $tds->item(1)->nodeValue ),
					'date' => trim( $tds->item(2)->nodeValue ),
					'time' => trim( $tds->item(3)->nodeValue ),
					'county' => trim( $tds->item(4)->nodeValue ),
					'location' => trim( $tds->item(5)->nodeValue ),
				);
				
			}
			
			return $incidents;
			
		}
		
		public function get_page ( ) {
			
			$options = array(
				'http' => array(
					'timeout' => 10,
				),
			);
			
			$context = stream_context_create( $options );
			
			$contents = file_get_contents( self::BASE_URL, false, $context );
			
			return $contents;
			
		}
		
		public function file_put_csv( $filename, $fields, $flags = 0, $delimiter = ',', $enclosure = '"' ) {
			
			// we wrap the csv around a temp memory stream so we can pass flags straight into file_put_contents afterwards
			$h = fopen( 'php://temp', 'w+' );
			
			fputcsv( $h, $fields, $delimiter, $enclosure );
			
			rewind( $h );
			
			$contents = stream_get_contents( $h );
			
			fclose( $h );
			
			$result = file_put_contents( $filename, $contents, $flags );
			
			return $result;
			
		}
		
	}

?>