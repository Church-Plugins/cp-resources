<?php
use ChurchPlugins\Helpers;

try {
	$resource = new \CP_Resources\Controllers\Resource( get_the_ID(), true );
} catch ( \ChurchPlugins\Exception $e ) {
	error_log( $e );
	return;
}

$classes = apply_filters( 'cp_resources_resource_list_classes', [ 'cp-resources-list-resource', 'cp-resource--' . get_the_ID() ], $resource );
?>

<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

	<div class="cp-resources-list-resource--thumb">
		<div class="cp-resources-list-resource--thumb--canvas" style="background: url(<?php echo esc_url( $resource->get_thumbnail() ); ?>) 0% 0% / cover;">
			<?php if ( $resource->get_thumbnail() ) : ?>
				<img alt="<?php esc_attr( $resource->get_title() ); ?>" src="<?php echo esc_url( $resource->get_thumbnail() ); ?>">
			<?php endif; ?>
		</div>
	</div>

	<div class="cp-resources-list-resource--main">

		<h1 class="cp-resources-list-resource--title"><a href="<?php echo esc_url( $resource->get_url() ); ?>"><?php echo $resource->get_title(); ?></a></h1>

		<?php if ( $type = $resource->get_type_label() ) : ?>
			<p class="cp-resources-list-resource--type"><?php echo esc_html( $type ); ?></p>
		<?php endif; ?>

	</div>


</article>
