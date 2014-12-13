<?php
	
	date_default_timezone_set('America/New_York');
	error_reporting(-1);
	ini_set('display_errors', true);
	
	class Stats {
		
		private $db;
		
		public function __construct ( $data_file = 'data.db' ) {
			
			$this->db = new \PDO( 'sqlite:' . $data_file );
			$this->db->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
			$this->db->setAttribute( \PDO::ATTR_ORACLE_NULLS, \PDO::NULL_EMPTY_STRING );
			$this->db->setAttribute( \PDO::ATTR_EMULATE_PREPARES, false );
			$this->db->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ );
			
			$this->db->query( 'PRAGMA journal_mode = MEMORY' );
			$this->db->query( 'PRAGMA temp_store = MEMORY' );
			$this->db->query( 'PRAGMA cache_size = 4000' );
			$this->db->query( 'PRAGMA read_uncommitted = true' );
			
			// create the temp view we use to format dates properly
			$this->create_temp_view();
			
		}
		
		public function dump_stats ( $export_file = 'stats.json' ) {
			
			$stats = $this->get_stats();
			
			$result = file_put_contents( $export_file, json_encode( $stats, JSON_NUMERIC_CHECK ) );
			
			if ( $result === false ) {
				throw new \Exception('Unable to write to file ' . $export_file );
			}
			else if ( $result === 0 ) {
				throw new \Exception('We wrote 0 bytes. Probably a bad json_encode?');
			}
			else {
				echo 'Wrote ' . array_sum( $stats['dayofweek'] ) . ' incidents over ' . count( $stats['weekly'] ) . ' weeks, ' . count( $stats['dayofweek'] ) . ' days, and ' . count( $stats['typeofincident'] ) . ' types.' . "\n";
			}
			
		}
		
		private function get_stats ( ) {
						
			$stats = array(
				'weekly' => $this->get_incidents_per_week(),
				'dayofweek' => $this->get_incidents_per_day_of_week(),
				'typeofincident' => $this->get_incidents_per_type(),
				'typebyweek' => $this->get_incidents_per_type_by_week(),
			);
						
			return $stats;
			
		}
		
		private function get_incidents_per_type ( ) {
			
			$sql_select = <<<SQL
select
	type,
	count( distinct incident ) as num_incidents
from
	vIncidents
group by
	type
having
	count( distinct incident ) >= 10
SQL;

			$types = array();
			foreach ( $this->db->query( $sql_select ) as $type ) {
				$types[ $type->type ] = intval( $type->num_incidents );
			}
			
			return $types;
			
		}
		
		private function get_incidents_per_day_of_week ( ) {
			
			$sql_select = <<<SQL
select
	strftime( '%w', datetime_incident ) as dayofweek,
	count( distinct incident ) as num_incidents
from
	vIncidents
group by
	strftime( '%w', datetime_incident )
order by
	dayofweek
SQL;

			$days = array();
			foreach ( $this->db->query( $sql_select ) as $day ) {
				$days[ intval( $day->dayofweek ) ] = intval( $day->num_incidents );
			}
			
			return $days;
			
		}
		
		private function get_incidents_per_week ( ) {
			
			// create the temp view we use 
			$sql_create_view = <<<SQL
create temp view vTempPerWeek as
select
	strftime( '%Y', datetime_incident ) || strftime( '%W', datetime_incident ) as week,
	count( distinct incident ) as num_incidents
from
	vIncidents
group by
	week
order by week
SQL;

$sql_select = <<<SQL
select
	*
from
	vTempPerWeek
where
	-- exclude our first and last weeks, which are partial
	week > ( select min( week ) from vTempPerWeek )
	and week < ( select max( week ) from vTempPerWeek )
SQL;

			// create the temp view
			$this->db->query( $sql_create_view );
			
			// now get our datas
			$weeks = array();
			foreach ( $this->db->query( $sql_select ) as $week ) {
				$weeks[ intval( $week->week ) ] = intval( $week->num_incidents );
			}
			
			return $weeks;
			
		}
		
		private function get_incidents_per_type_by_week ( ) {
			
			// create the temp view we use 
			$sql_create_view = <<<SQL
create temp view vTempPerTypeByWeek as
select
	type,
	strftime( '%Y', datetime_incident ) || strftime( '%W', datetime_incident ) as week,
	count(distinct incident) as num_incidents
from
	vIncidents
group by
	type,
	week
having
	num_incidents > 10
order by
	type,
	week
SQL;

$sql_select = <<<SQL
select
	*
from
	vTempPerTypeByWeek
where
	-- exclude our first and last weeks, which are partial
	week > ( select min( week ) from vTempPerWeek )
	and week < ( select max( week ) from vTempPerWeek )
SQL;

			// create the temp view
			$this->db->query( $sql_create_view );
			
			// now get our datas
			$weeks = array();
			foreach ( $this->db->query( $sql_select ) as $week ) {
				if ( !isset( $weeks[ $week->type ] ) ) {
					$weeks[ $week->type ] = array();
				}
				
				$weeks[ $week->type ][ intval( $week->week ) ] = intval( $week->num_incidents );
			}
			
			return $weeks;
			
		}
		
		private function create_temp_view ( ) {
			
			$sql = <<<SQL
create temp view vIncidents as
select
	type,
	status,
	date,
	time,
	county,
	location,
	hash,
	incident,
	date_incident,
	date_entered,
	-- the ISO8601 string from PHP looks like 2005-08-15T15:52:01+0000
	datetime( substr( date_incident, 1, length( date_incident) - 2 ) || ':' || substr( date_incident, -2 ) ) as datetime_incident
from
	incidents
where
	-- eliminate the two weird rows from 2011 and 2012 that we somehow have in our data
	date_incident > '2013-01-01'
SQL;
	
			$this->db->query( $sql );
			
		}
		
	}
	
	$s = new Stats( '/Users/chris/Desktop/schptrafficinfo.db');
	$s->dump_stats();
	
?>