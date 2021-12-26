<article <?php post_class( 'qodef-blog-item qodef-e' ); ?>>
	<div class="qodef-e-inner">
		<?php
		// Include post format part
		laurits_template_part( 'blog', 'templates/parts/post-format/quote' );
		?>
		<div class="qodef-e-content">
			<div class="qodef-e-top-holder">
				<div class="qodef-e-info">
					<?php
					// Include post date info
					laurits_template_part( 'blog', 'templates/parts/post-info/date' );
					?>
				</div>
			</div>
			<div class="qodef-e-text">
				<?php
				// Include post content
				the_content();

				// Hook to include additional content after blog single content
				do_action( 'laurits_action_after_blog_single_content' );
				?>
			</div>
			<div class="qodef-e-bottom-holder">
				<div class="qodef-e-left qodef-e-info">
					<?php
					// Include post category info
					laurits_template_part( 'blog', 'templates/parts/post-info/categories' );
					?>
				</div>
				<div class="qodef-e-right qodef-e-info">
					<?php
					// Include social share
					do_action( 'laurits_action_after_blog_single_social_share' );
					?>
				</div>
			</div>
		</div>
	</div>
</article>
