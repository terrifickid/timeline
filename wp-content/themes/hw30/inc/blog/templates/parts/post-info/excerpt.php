<?php

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
	$excerpt = get_the_excerpt();

	if ( ! isset( $excerpt_length ) || '' === $excerpt_length ) {
		$excerpt_length = laurits_get_blog_list_excerpt_length();
	}

	if ( ! empty( $excerpt ) && 0 !== $excerpt_length ) {
		$new_excerpt = substr( $excerpt, 0, intval( $excerpt_length ) );
		?>
		<p itemprop="description" class="qodef-e-excerpt"><?php echo esc_html( strip_tags( strip_shortcodes( $new_excerpt ) ) ); ?></p>
	<?php }
} ?>
