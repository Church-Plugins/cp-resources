<?php
global $post;
$post_orig = $post;
if ( empty( $args ) ) {
	return;
}

$resources = \CP_Resources\Models\Resource::get_all_resources( $args['id'] );

if ( empty( $resources ) ) {
	return;
}

$post = get_post( $args['id'] );
setup_postdata( $post->ID );

$container_classes = [ 'cp-widget', 'cp-resources-widget', 'cp-resources-widget-' . $post->ID, 'cp-resources-widget--' . get_post_type() ];

if ( count( $resources ) > 1 ) {
	$container_classes[] = 'cp-resources--feature-first';
}

$container_classes = apply_filters( 'cp_resources_widget_container_classes', $container_classes );
?>

<?php do_action( 'cp_resources_item_resources_before', $post ); ?>

<div class="<?php echo esc_attr( implode( ' ', $container_classes) ); ?>">
	<h5><?php echo cp_resources()->setup->post_types->resource->plural_label; ?></h5>

	<div class="cp-resources-list">

		<?php foreach( $resources as $resource ) : ?>
			<?php try {
				$resource = new \CP_Resources\Controllers\Resource( $resource->id );
				if ( 'publish' !== get_post_status( $resource->post->ID ) ) {
					continue;
				}
			} catch( \ChurchPlugins\Exception $e ) {
				error_log( $e );
			} ?>
			<div class="cp-resources-list--resource">
				<a href="<?php echo esc_url( $resource->get_url() ); ?>" target="_blank"><?php echo $resource->get_icon(); ?> <?php echo $resource->get_title(); ?></a>
			</div>
		<?php endforeach; ?>

	</div>

</div>

<?php
do_action( 'cp_resources_item_resources_after', $post );
wp_reset_postdata();
$post = $post_orig;
?>
