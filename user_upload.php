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
?>