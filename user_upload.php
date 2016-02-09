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
	
	//Display the inputted command line options (temporary)
	var_dump($options);
	
	//For the CSV file
	//Open the file given in --file command line option
		//It should be a CSV file (haven't checked that though)
		//should this array reference be in a separate variable?
	$file = fopen($options["file"],"r");
	
	//var_dump(fgetcsv($file));
	//or could use print_r
	while(! feof($file)) {
		var_dump(fgetcsv($file));
	}
		
	fclose($file);
?>