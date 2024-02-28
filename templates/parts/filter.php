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
			<a href="#" class="cp-resources-filter--toggle--button cp-button"><span><?php esc_html_e( 'Filter', 'cp-library' ); ?></span> <?php echo Helpers::get_icon( 'filter' ); ?></a>
		</div>
		<?php endif; ?>

		<?php
		foreach ( $taxonomies as $tax ) :
			$terms = get_terms( [ 'taxonomy' => $tax->taxonomy, 'hide_empty' => true ] );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}
			?>

			<div class="cp-resources-filter--<?php echo esc_attr( $tax->taxonomy ); ?> cp-resources-filter--has-dropdown" <?php echo $display; ?>>
				<a href="#" class="cp-resources-filter--dropdown-button cp-button is-light"><?php echo esc_html( $tax->plural_label ); ?></a>
				<div class="cp-resources-filter--dropdown">
					<?php
					foreach ( $terms as $term ) :
						if ( in_array( $term->term_id, $hidden_types ) ) {
							continue;
						}

						if ( ! cp_resources()->setup->post_types->resource->has_visible_resources( $term->term_id, $term->taxonomy ) ) {
							continue;
						}

						?>
						<label>
							<input type="checkbox" <?php checked( in_array( $term->slug, Helpers::get_param( $_GET, $tax->taxonomy, [] ) ) ); ?> name="<?php echo esc_attr( $tax->taxonomy ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>" />
							<span class="cp-term-label"><?php echo esc_html( $term->name ); ?></span>
							<span class="cp-term-count">(<?php echo absint( $term->count ); ?>)</span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach;?>

		<div class="cp-resources-filter--search">
			<div class="cp-resources-filter--search--box">
				<button type="submit"><span class="material-icons-outlined">search</span></button>
				<input class="cp-resources--filter--search-input" type="text" name="<?php echo empty( Helpers::get_param( $_GET, 'search' ) ) ? '' : 'search'; ?>" value="<?php echo esc_attr( Helpers::get_param( $_GET, 'search' ) ); ?>" placeholder="<?php esc_attr_e( 'Search', 'cp-library' ); ?>" />
			</div>
		</div>

	</form>
</div>
