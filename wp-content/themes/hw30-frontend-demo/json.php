<?php
/*
Template Name: JSON layout

*/
// Page code here..

echo 'test!';
// WP_Query arguments
$args = array(
	'post_type'              => array( 'h_timeline' ),
	'nopaging'               => true,
  'posts_per_page'         => -1
);

// The Query
$query = new WP_Query( $args );
