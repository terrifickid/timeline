<?php
$quote_meta = get_post_meta( get_the_ID(), 'qodef_post_format_quote_text', true );
$quote_text = ! empty( $quote_meta ) ? $quote_meta : get_the_title();

if ( ! empty( $quote_text ) ) {
	$quote_author       = get_post_meta( get_the_ID(), 'qodef_post_format_quote_author', true );
	$quote_author_title = get_post_meta( get_the_ID(), 'qodef_post_format_quote_author_title', true );
	$title_tag          = isset( $title_tag ) && ! empty( $title_tag ) ? $title_tag : 'h3';
	$author_title_tag   = isset( $author_title_tag ) && ! empty( $author_title_tag ) ? $author_title_tag : 'span';
	?>
	<div class="qodef-e-quote">
	<span class="qodef-e-tagline"><?php echo esc_html__( 'Quote', 'laurits' ); ?></span>
		<<?php echo esc_attr( $title_tag ); ?> class="qodef-e-quote-text"><?php echo esc_html( $quote_text ); ?></<?php echo esc_attr( $title_tag ); ?>>
		<div class="qodef-e-author-info">
		<?php if ( ! empty( $quote_author ) ) { ?>
			<<?php echo esc_attr( $author_title_tag ); ?> class="qodef-e-quote-author"><?php echo esc_html( $quote_author ); ?></<?php echo esc_attr( $author_title_tag ); ?>>
		<?php } ?>
		<?php if ( ! empty( $quote_author_title ) ) { ?>
			<<?php echo esc_attr( $author_title_tag ); ?> class="qodef-e-quote-author-title"><?php echo esc_html( $quote_author_title ); ?></<?php echo esc_attr( $author_title_tag ); ?>>
		<?php } ?>
		</div>
		<?php if ( ! is_single() ) { ?>
			<a itemprop="url" class="qodef-e-quote-url" href="<?php the_permalink(); ?>" target="_self"></a>
		<?php } ?>
	</div>
<?php } ?>
