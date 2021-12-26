<div class="qodef-e-media">
	<?php
	switch ( get_post_format() ) {
		case 'gallery':
			laurits_template_part( 'blog', 'templates/parts/post-format/gallery' );
			break;
		case 'video':
			laurits_template_part( 'blog', 'templates/parts/post-format/video' );
			break;
		case 'audio':
			laurits_template_part( 'blog', 'templates/parts/post-format/audio' );
			break;
		default:
			laurits_template_part( 'blog', 'templates/parts/post-info/image' );
			break;
	}
	?>
</div>
