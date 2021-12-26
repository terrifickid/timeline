<div id="qodef-page-comments">
	<?php if ( have_comments() ) {
		$comments_number = get_comments_number();
		?>
		<div id="qodef-page-comments-list" class="qodef-m">
			<?php // translators: %s - number of comments value ?>
			<h3 class="qodef-m-title"><?php echo esc_html( sprintf( _n( 'Comment (%s)', 'Comments (%s)', intval( $comments_number ), 'laurits' ), intval( $comments_number ) ) ); ?></h3>
			<ul class="qodef-m-comments">
				<?php wp_list_comments( array_unique( array_merge( array( 'callback' => 'laurits_get_comments_list_template' ), apply_filters( 'laurits_filter_comments_list_template_callback', array() ) ) ) ); ?>
			</ul>

			<?php if ( get_comment_pages_count() > 1 ) { ?>
				<div class="qodef-m-pagination qodef--wp">
					<?php
					the_comments_pagination(
						array(
							'prev_text'          => laurits_get_svg_icon( 'pagination-arrow-left' ),
							'next_text'          => laurits_get_svg_icon( 'pagination-arrow-right' ),
							'before_page_number' => '0',
						)
					);
					?>
				</div>
			<?php } ?>
		</div>
	<?php } ?>
	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) { ?>
		<p class="qodef-page-comments-not-found"><?php esc_html_e( 'Comments are closed.', 'laurits' ); ?></p>
	<?php } ?>
	<div id="qodef-page-comments-form">
		<?php comment_form( laurits_get_comment_form_args() ); ?>
	</div>
</div>
