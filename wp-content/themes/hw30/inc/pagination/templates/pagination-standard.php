<?php if ( isset( $query_result ) && intval( $query_result->max_num_pages ) > 1 ) {
	$total      = intval( $query_result->max_num_pages );
	$current    = isset( $next_page ) && ! empty( $next_page ) ? intval( $next_page ) : 1;
	$mid_size   = 2;
	$end_size   = $total - $current;
	$dots       = '<span class="qodef-m-pagination-item qodef--dots">' . esc_html__( '&hellip;', 'laurits' ) . '</span>';
	$set_dots   = false;
	$dots_added = false;

	$hidden       = 'qodef--hide';
	$prev_classes = array();
	$next_classes = array();

	if ( 1 === $current ) {
		$prev_classes[] = $hidden;
	}

	if ( $total === $current ) {
		$next_classes[] = $hidden;
	}
	?>
	<div class="qodef-m-pagination qodef--standard">
		<div class="qodef-m-pagination-inner">
			<nav class="qodef-m-pagination-items">
				<a class="qodef-m-pagination-item qodef--prev <?php echo esc_attr( implode( ' ', $prev_classes ) ); ?>" href="#" data-paged="1">
					<?php laurits_render_svg_icon( 'pagination-arrow-left' ); ?>
				</a>
				<?php
				for ( $i = 1; $i <= $total; $i ++ ) {
					$classes     = array();
					$formatted_i = sprintf( '%02d', $i );

					// Added active item class
					if ( $current === $i ) {
						$classes[] = 'qodef--active';
					}

					// Added first item additional class to remove left margin, because prev arrow is hidden
					if ( 1 === $current && 1 === $i ) {
						$classes[] = 'qodef-prev--hidden';
					}

					// Added items hidden class from dots to the end, exclude first and last item
					if ( $set_dots && ! in_array( $i, array( 1, $total ), true ) && $i > $current ) {
						$classes[] = $hidden;
					}

					if ( ! $set_dots && $total > ( $mid_size + 1 ) ) {

						if ( 1 === $current && ( $mid_size + 1 ) === $i ) {
							$set_dots  = true;
							$classes[] = $hidden;
						} elseif ( $current > 1 ) {

							// Added items hidden class from beginning to the current item - mid size, exclude first and last item
							if ( $i <= ( $current - $mid_size ) && ! in_array( $i, array( 1, $total ), true ) ) {
								$classes[] = $hidden;

								// Added dots markup between first item and current item
								if ( ( $current - $mid_size ) === $i ) {
									echo wp_kses_post( $dots );
								}
							}

							if ( ( $current + 1 ) === $i && $i <= ( $total - $mid_size ) ) {
								$set_dots = true;
							}
						}
					}
					?>
					<a class="qodef-m-pagination-item qodef--number qodef--number-<?php echo esc_attr( $i ); ?> <?php echo esc_attr( implode( ' ', $classes ) ); ?>" href="#" data-paged="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $formatted_i ); ?></a>
					<?php
					// Added dots markup
					if ( $set_dots && ! $dots_added ) {
						echo wp_kses_post( $dots );
						$dots_added = true;
					}
					?>
				<?php } ?>
				<a class="qodef-m-pagination-item qodef--next <?php echo esc_attr( implode( ' ', $next_classes ) ); ?>" href="#" data-paged="2">
					<?php laurits_render_svg_icon( 'pagination-arrow-right' ); ?>
				</a>
			</nav>
		</div>
	</div>
	<?php
	// Include loading spinner
	laurits_render_svg_icon( 'spinner', 'qodef-m-pagination-spinner' );
	?>
<?php } ?>
