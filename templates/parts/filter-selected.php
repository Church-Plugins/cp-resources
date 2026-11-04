<?php
use ChurchPlugins\Helpers;

$taxonomies = cp_resources()->setup->taxonomies->get_objects();
$uri = explode( '?', $_SERVER['REQUEST_URI'] )[0];
$get = $_GET;
?>
<div class="cp-resources-filter--filters">
	<?php foreach ( [ 'search', 's' ] as $search_key ) : if ( empty( $_GET[ $search_key ] ) ) continue; $search_get = $get; unset( $search_get[ $search_key ] ); ?>
		<a href="<?php echo esc_url( add_query_arg( $search_get, $uri ) ); ?>" class="cp-resources-filter--filters--filter"><?php echo esc_html( __( 'Search:' ) . ' ' . Helpers::get_request( $search_key ) ); ?></a>
	<?php endforeach; ?>

	<?php foreach ( $taxonomies as $tax ) : if ( empty( $_GET[ $tax->taxonomy ] ) ) continue; ?>
		<?php foreach( $_GET[ $tax->taxonomy ] as $slug ) :
			if ( ! $term = get_term_by( 'slug', $slug, $tax->taxonomy ) ) {
				continue;
			}

			$get = $_GET;
			unset( $get[ $tax->taxonomy ][ array_search( $slug, $get[ $tax->taxonomy ] ) ] );
			?>
			<a href="<?php echo esc_url( add_query_arg( $get, $uri ) ); ?>" class="cp-resources-filter--filters--filter"><?php echo $term->name; ?></a>
		<?php endforeach; ?>
	<?php endforeach; ?>
</div>
