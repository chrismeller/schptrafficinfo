<?php

	date_default_timezone_set( 'America/New_York' );

	error_reporting(-1);
	ini_set('display_errors', true);
	
	require('schptrafficinfo.php');
	
	$pdo = new PDO( 'sqlite:data.db' );
	$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$pdo->setAttribute( PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING );
	$pdo->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
	$pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ );
	
	// see if our table exists
	try {
		// simply trying to prepare a query that references a table that does not exist will throw an exception in sqlite
		$pdo->prepare( 'select count(*) from incidents' );
	}
	catch ( PDOException $e ) {
		// create the table
		$create_sql = <<<CREATESQL
create table incidents (
	type varchar(255) not null,
	status varchar(255) not null,
	date varchar(50) not null,
	time varchar(50) not null,
	county varchar(50) not null,
	location varchar(50) not null,
	hash varchar(40) not null PRIMARY KEY
);
CREATESQL;

		$pdo->query( $create_sql );
	}
	
	// now make sure our new incident ID field is there
	try {
		$pdo->prepare( 'select incident from incidents limit 1' );
	}
	catch ( PDOException $e ) {
		$update_sql = <<<UPDATESQL
alter table incidents
add column incident varchar(255) null;
UPDATESQL;

		$pdo->query( $update_sql );
		
		// and update all the incidents -- this sucks
		$migrate_update = $pdo->prepare( 'update incidents set incident = ? where hash = ?' );
		$migrate_select = $pdo->prepare( 'select * from incidents where incident is null' );
		$migrate_select->execute();
		
		while ( $result = $migrate_select->fetch( PDO::FETCH_ASSOC ) ) {
			
			$incident_hash = sha1( implode( '|', array( $result['type'], $result['date'], $result['time'], $result['county'] ) ) );
			
			$migrate_update->execute(
				array(
					$incident_hash,
					$result['hash'],
				)
			);
			
		}
	}
	
	// now make sure our new date_incident and date_entered fields are there
	try {
		$pdo->prepare( 'select date_entered from incidents limit 1' );
	}
	catch ( PDOException $e ) {
		$update_sql1 = <<<UPDATESQL
alter table incidents
add column date_incident varchar(24) null;
UPDATESQL;

		$pdo->query( $update_sql1 );
		
		$update_sql2 = <<<UPDATESQL
alter table incidents
add column date_entered varchar(24) null;
UPDATESQL;

		$pdo->query( $update_sql2 );
		
		// we're not going to update old entries, they will simply have null values
	}
	
	
	$insert = $pdo->prepare( 'insert into incidents ( type, status, date, time, county, location, hash, incident, date_incident, date_entered ) values ( :type, :status, :date, :time, :county, :location, :hash, :incident, :date_incident, :date_entered )' );
	
	$s = new SCHPTrafficInfo\SCHPTrafficInfo();
	
	$incidents = $s->get_incidents();
	
	$inserted = 0;
	$duplicate = 0;
	foreach ( $incidents as $incident ) {
		
		$now = new \DateTime();
		
		// start with the raw fields we don't modify
		$db_incident = array(
			'type' => $incident['type'],
			'status' => $incident['status'],
			'date' => $incident['date'],
			'time' => $incident['time'],
			'county' => $incident['county'],
			'location' => $incident['location'],
		);
		
		// then the ones we have to do a little calculation on
		$db_incident['hash'] = sha1( implode( '|', array( $incident['type'], $incident['status'], $incident['date'], $incident['time'], $incident['county'], $incident['location'] ) ) );
		$db_incident['incident'] = sha1( implode( '|', array( $incident['type'], $incident['date'], $incident['time'], $incident['county'] ) ) );
		$db_incident['date_entered'] = $now->format( \DateTime::ISO8601 );
		$db_incident['date_incident'] = $incident['datetime']->format( \DateTime::ISO8601 );
		
		try {
			$insert->execute( $db_incident );
			
			$inserted++;
		}
		catch ( PDOException $e ) {
			
			if ( $e->getCode() == 23000 ) {
				// this is an integrity constraint violation - we don't do updates
				// just ignore it
				$duplicate++;
			}
			else {
				echo $e->getMessage() . "\n";
			}
			
		}
		
	}
	
	echo 'Got ' . count( $incidents ) . ' incidents, wrote ' . $inserted . ', ' . $duplicate . ' duplicates' . "\n";

?>