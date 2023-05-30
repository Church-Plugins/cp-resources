<?php
use ChurchPlugins\Helpers;

$taxonomies = cp_resources()->setup->taxonomies->get_objects();
$hidden_types = cp_resources()->setup->taxonomies->type->get_types_by_visibility( 'hide' );

$uri = explode( '?', $_SERVER['REQUEST_URI'] )[0];
$get = $_GET;
$display = '';

if ( empty( $get ) ) {
	$display = ''; // 'style="display: none;"';
}

$display = apply_filters( 'cpl_filters_display', $display );
?>
<div class="cp-resources-filter">

	<form method="get" class="cp-resources-filter--form">

		<?php if ( 0 ) : // disable Filter toggle ?>
		<div class="cp-resources-filter--toggle">
			<a href="#" class="cp-resources-filter--toggle--button cp-button"><span><?php _e( 'Filter', 'cp-library' ); ?></span> <?php echo Helpers::get_icon( 'filter' ); ?></a>
		</div>
		<?php endif; ?>

		<?php foreach( $taxonomies as $tax ) :
			$terms = get_terms( [ 'taxonomy' => $tax->taxonomy ] );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			} ?>

			<div class="cp-resources-filter--<?php echo esc_attr( $tax->taxonomy ); ?> cp-resources-filter--has-dropdown" <?php echo $display; ?>>
				<a href="#" class="cp-resources-filter--dropdown-button cp-button is-light"><?php echo $tax->plural_label; ?></a>
				<div class="cp-resources-filter--dropdown">
					<?php foreach ( $terms as $term ) : if ( in_array( $term->term_id, $hidden_types ) ) continue; ?>
						<label>
							<input type="checkbox" <?php checked( in_array( $term->slug, Helpers::get_param( $_GET, $tax->taxonomy, [] ) ) ); ?> name="<?php echo esc_attr( $tax->taxonomy ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>"/> <?php echo esc_html( $term->name ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="cp-resources-filter--search">
			<div class="cp-resources-filter--search--box">
				<button type="submit"><span class="material-icons-outlined">search</span></button>
				<input type="text" name="s" value="<?php echo Helpers::get_param( $_GET, 's' ); ?>" placeholder="<?php _e( 'Search', 'cp-library' ); ?>"/>
			</div>
		</div>

	</form>

	<script>

		var $ = jQuery;
	  	$('.cp-resources-filter--toggle--button').on('click', function(e) {
			e.preventDefault();
			$('.cp-resources-filter--has-dropdown').toggle();
		});

	  	$( '.cp-resources-filter--form input[type=checkbox]' ).on( 'change',
			function() {

				// Munge the URL to discard pagination when fiilter options change
				var form = $( this ).parents( 'form.cp-resources-filter--form' );
				var location = window.location;
				var baseUrl = location.protocol + '//' + location.hostname;
				var pathSplit = location.pathname.split( '/' );
				let finalPath = '';

				// Get the URL before the `page` element
				var gotBoundary = false;
				$( pathSplit ).each(
					function(index, token) {

						if( 'page' === token ) {
							gotBoundary = true;
						}
						if( !gotBoundary ) {

							if( '' === token ) {
								if( !finalPath.endsWith( '/' ) ) {
									finalPath += '/';
								}
							} else {
								finalPath += token;
								if( !finalPath.endsWith( '/' ) ) {
									finalPath += '/';
								}
							}

						}
					}
				);
				// Finish and add already-used GET params
				if( !finalPath.endsWith( '/' ) ) {
					finalPath += '/';
				}
				if( location.search && location.search.length > 0 ) {
					finalPath += location.search;
				}
				// Set form property and do it
				$( form ).attr( 'action', baseUrl + finalPath );
				$('.cp-resources-filter--form').submit();
			});

		$('.cp-resources-filter--has-dropdown a').on( 'click', function(e) {
			e.preventDefault();
			$(this).parent().toggleClass('open');
		})
	</script>
</div>
