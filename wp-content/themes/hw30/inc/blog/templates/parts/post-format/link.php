<?php
$link_url_meta  = get_post_meta( get_the_ID(), 'qodef_post_format_link', true );
$link_url       = ! empty( $link_url_meta ) ? $link_url_meta : get_the_permalink();
$link_text_meta = get_post_meta( get_the_ID(), 'qodef_post_format_link_text', true );

if ( ! empty( $link_url ) ) {
	$link_text = ! empty( $link_text_meta ) ? $link_text_meta : get_the_title();
	$title_tag = isset( $title_tag ) && ! empty( $title_tag ) ? $title_tag : 'h3';
	?>
	<div class="qodef-e-link">
	<span class="qodef-e-tagline"><?php echo esc_html__( 'Link', 'laurits' ); ?></span>
		<<?php echo esc_attr( $title_tag ); ?> class="qodef-e-link-text"><?php echo esc_html( $link_text ); ?></<?php echo esc_attr( $title_tag ); ?>>
	<a class="qodef-shortcode qodef-m  qodef-button qodef-layout--textual  qodef-html--link" href="<?php echo esc_url( $link_url ); ?>" target="_blank">
		<span class="qodef-m-text"><?php echo esc_html__( 'View Link', 'laurits' ); ?></span>
	</a>
	</div>
<?php } ?>
