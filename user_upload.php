#!/usr/local/bin/php
<?php 
	//Take in the command line options
	$options = getopt("u:p:h:",array (
		'file:',
		'create_table',
		'dry_run',
		'help'
		)
	);
	
	$dry_run = false;
	$create_table = false;
	foreach ($options as $key => $value) {
		if ($key == 'dry_run') {
			$dry_run = true;
		}
		if ($key == 'create_table') {
			$create_table = true;
		}
	}
	
	$filename = $options["file"];
	$lines = csvProcessing($filename);

	//MySQL
	
	//Connect to MySQL using the host, user and password given in the command-line options
	$conn = mysqli_connect($options["h"],$options["u"],$options["p"]);
	
	if (!$conn) {
		die("Connection failed: " . mysqli_connect_error());
	}
	
	$filtered = [];
	//Go through each line to check
	foreach($lines as &$value) {
		//Trim leading and trailing whitespace from email so they are not declared invalid due to whitespace
		$value[2] = trim($value[2]);
		//Check if the email is valid
		if(filter_var($value[2], FILTER_VALIDATE_EMAIL))
		{
			//Escape any characters that may interfere with the SQL query, eg the apostrophe in surname
			$value[0] = mysqli_real_escape_string($conn, $value[0]);
			$value[1] = mysqli_real_escape_string($conn, $value[1]);
			$value[2] = mysqli_real_escape_string($conn, $value[2]);
			//Put the data into a new array just for valid entries
			$filtered[] = $value;
		}
		//If the email is invalid, output an error
		else {
			error_log("Email invalid: " . $value[2]);
		}
	}
	
	if($dry_run) {
		mysqli_close($conn);
		return;
	}
	
	dbCreate($conn);
	
	//If the create_table option was specified, we have created the table now
	//So we can quit
	if($create_table) {
		mysqli_close($conn);
		return;
	}
	
	insertData($filtered, $conn);
	
	//Close the MySQL connection
	mysqli_close($conn);
	
	function csvProcessing($filename) {
		//For the CSV file
		//Open the file given in --file command line option
		//It should be a CSV file (haven't checked that though)
		//should this array reference be in a separate variable?
		$file = fopen($filename,"r");
		
		//create an empty array to put the csv file into
		$lines = [];
	
		//load each line of the CSV file, to the end of the file, into an array
		while(! feof($file)) {
		$lines[] = fgetcsv($file);
		}
		
		//Close the CSV file
		fclose($file);
	
		//removing the first line of the CSV file because it has the headings name, surname, email
		array_shift($lines);
		//removing the last line because it is blank
		array_pop($lines);
		
		//capitalise each first name and last name
		foreach ($lines as &$value) {
			$value[0] = ucfirst($value[0]);
			$value[1] = ucfirst($value[1]);
			$value[2] = strtolower($value[2]);
		}
		
		return $lines;
	}
	
	function dbCreate($conn) {
		//Try to connect, to see if the database exists
		//If it doesn't exist, create one
		if(!mysqli_select_db($conn,'myDB')) {
			echo "Existing database not found, creating DB\n";
			$sql = "CREATE DATABASE myDB";
			//If the database is created successfully, print a message
			if (mysqli_query($conn, $sql)) {
				echo "Database created successfully\n";
			}
		}
		//If the database already exists, print a message
		else {
			echo "Database already exists, using existing database\n";
		}
		
		//If the table already exists, drop it.
		$sql = "DROP TABLE IF EXISTS users";
		if(!mysqli_query($conn, $sql)) {
			echo "Drop if exists failed\n";
		}
		
		//Create the table
		$sql = "CREATE TABLE users
			(
				name varchar(255),
				surname varchar(255),
				email varchar(255)
			)";
		
		if (mysqli_query($conn, $sql)) {
			echo "Table created successfully\n";
		}
		else {
			echo "Didn't create table\n";
		}
		
		//Create unique index on email
		$sql = "CREATE UNIQUE INDEX email_index ON users (email)";
	
		if (mysqli_query($conn, $sql)) {
			echo "Index created successfully\n";
		}
	}
	
	function insertData($filtered, $conn) {
		//Go through each line to check and then insert
		foreach($filtered as &$value)
		{
			$sql = "INSERT INTO users (name, surname, email) VALUES ('$value[0]', '$value[1]', '$value[2]')";
			mysqli_query($conn, $sql);
		}
	}
	
?>