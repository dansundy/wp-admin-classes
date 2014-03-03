# WordPress Admin Classes

Classes that build customizer pages, settings pages and meta boxes in posts.

## Usage

Place the admin classes directory in your WordPress theme. Include the class files in your `functions.php` file:

	// This is the functions.php file
	
	// Of course be sure to update the paths so that they're correct
	
	require( get_template_directory() . '/admin-classes/class-meta-boxes.php' );
	require( get_template_directory() . '/admin-classes/class-customizer.php' );
	require( get_template_directory() . '/admin-classes/class-settings.php' );
	

From here, each class is used in a specific way with a structured array of data.

## Customizer

## Settings Pages

## Meta Boxes