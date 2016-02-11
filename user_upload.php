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
	
	//Set the variables for command line options that affect control flow
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
	
	//Set the variables for command line options that have parameters
	$filename = $options["file"];
	$dbHost = $options["h"];
	$dbUser = $options["u"];
	$dbPass = $options["p"];
	
	//Main control flow
	
	//If dry run option is selected, we will just process the lines, but not interact with the database
	//Wasn't really sure of this, what the dry run meant so will just leave it like this
	if($dry_run) {
		//Process the CSV file - loading, capitalization, email validation etc
		$lines = csvProcessing($filename);
		//Finish
		return;
	}
	//If create table option is selected, we will connect to the database and create the table, but not interact with the CSV file
	else if($create_table) {
		//Connect to the MySQL database
		$conn = dbConnect($dbHost, $dbUser, $dbPass);
		//Create table (and database)
		dbCreate($conn);
		//Close the MySQL connection
		mysqli_close($conn);
		//Finish
		return;
	}
	//Otherwise, we will do everything
	else {
		//Process the CSV file - loading, capitalization, email validation etc
		$lines = csvProcessing($filename);
		//Connect to the MySQL database
		$conn = dbConnect($dbHost, $dbUser, $dbPass);
		//Create table (and database)
		dbCreate($conn);
		//Insert the CSV data into the table
		insertData($lines, $conn);
		//Close the MySQL connection
		mysqli_close($conn);
		//Finish
		return;
	}

	//Process the CSV file - loading, capitalization, email validation etc
	function csvProcessing($filename) {
		//Open the CSV file
		$file = fopen($filename,"r");
		
		//Create an empty array to put the csv file into
		$lines = [];
	
		//Load each line of the CSV file, to the end of the file, into the array
		while(! feof($file)) {
		$lines[] = fgetcsv($file);
		}
		
		//Close the CSV file
		fclose($file);
	
		//removing the first line of the CSV file because it has the headings name, surname, email
		array_shift($lines);
		//removing the last line because it is blank
		array_pop($lines);

		$filtered = [];
		//Go through each line
		foreach ($lines as &$value) {
			//Capitalise first name and last name
			$value[0] = ucfirst($value[0]);
			$value[1] = ucfirst($value[1]);
			//Email lower case
			$value[2] = strtolower($value[2]);
		
			//Trim leading and trailing whitespace from email so they are not declared invalid due to whitespace
			$value[2] = trim($value[2]);
			
			//Check if the email is valid
			if(filter_var($value[2], FILTER_VALIDATE_EMAIL))
			{
				//Put the data into a new array just for valid entries
				$filtered[] = $value;
			}
			//If the email is invalid, output an error
			else {
				error_log("Email invalid: " . $value[2]);
			}
		}
		//Return the processed and filtered array of data
		return $filtered;
	}
	
	//Connect to the database with the given information, returning a connection
	function dbConnect($host, $user, $pass) {
		//Connect
		$conn = mysqli_connect($host,$user,$pass);
		//If it didn't work, quit with error
		if (!$conn) {
			die("Connection failed: " . mysqli_connect_error());
		}
		return $conn;
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
	
	//Insert the CSV data into the table in the database
	function insertData($filtered, $conn) {
		//Go through each line to escape characters and then insert
		foreach($filtered as &$value)
		{
			//Escape any characters that may interfere with the SQL query, eg the apostrophe in surname
			$value[0] = mysqli_real_escape_string($conn, $value[0]);
			$value[1] = mysqli_real_escape_string($conn, $value[1]);
			$value[2] = mysqli_real_escape_string($conn, $value[2]);
		
			//Insert the values into the table
			$sql = "INSERT INTO users (name, surname, email) VALUES ('$value[0]', '$value[1]', '$value[2]')";
			mysqli_query($conn, $sql);
		}
	}
	
?>