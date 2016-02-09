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
	
	//(temporary) Display the inputted command line options
	var_dump($options);
	
	//For the CSV file
	//Open the file given in --file command line option
		//It should be a CSV file (haven't checked that though)
		//should this array reference be in a separate variable?
	$file = fopen($options["file"],"r");
	
	//create an empty array to put the csv file into
	$lines = [];
	
	//load each line of the CSV file, to the end of the file, into an array
	while(! feof($file)) {
		$lines[] = fgetcsv($file);
	}
		
	//Close the CSV file
	fclose($file);
	
	//(temporary) display the contents of the imported data
	var_dump($lines);
	
	//removing the first line of the CSV file because it has the headings name, surname, email
	array_shift($lines);
	//removing the last line because it is blank
	array_pop($lines);

	//capitalise each first name and last name
	foreach ($lines as &$value) {
		$value[0] = ucfirst($value[0]);
		$value[1] = ucfirst($value[1]);
	}
	
	//(temporary) display the contents of the imported data
	var_dump($lines);
		
?>