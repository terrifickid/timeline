<?php
$date_link = empty( get_the_title() ) && ! is_single() ? get_the_permalink() : get_month_link( get_the_time( 'Y' ), get_the_time( 'm' ) );
$classes   = '';
if ( is_single() || is_page() || is_archive() ) { // This check is to prevent classes for Gutenberg block
	$classes = 'qodef-e-info-date published updated';
}
?>
<a itemprop="dateCreated" href="<?php echo esc_url( $date_link ); ?>" class="<?php echo esc_attr( $classes ); ?>">
	<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="14" viewBox="0 0 16 14">
		<g>
			<rect width="16" height="14"/>
			<rect x="0.5" y="0.5" width="15" height="13"/>
		</g>
		<rect width="2" height="2" transform="translate(3 4)"/>
		<rect width="2" height="2" transform="translate(3 8)"/>
		<rect width="2" height="2" transform="translate(7 4)"/>
		<rect width="2" height="2" transform="translate(7 8)"/>
		<rect width="2" height="2" transform="translate(11 4)"/>
		<rect width="2" height="2" transform="translate(11 8)"/>
	</svg>
	<?php the_time( get_option( 'date_format' ) ); ?>
</a>
<div class="qodef-info-separator-end"></div>
