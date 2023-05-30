<?php
use CP_Resources\Templates;

$description = get_the_archive_description();
?>

<div class="cp-resources-archive">

	<?php do_action( 'cp_resources_before_archive' ); ?>

	<h1 class="page-title"><?php echo apply_filters( 'cp-resources-archive-title', cp_resources()->setup->post_types->resource->plural_label ); ?></h1>

	<div class="cp-resources-archive--container">

		<div class="cp-resources-archive--container--filter">
			<?php cp_resources()->templates->get_template_part( "parts/filter" ); ?>
		</div>

		<div class="cp-resources-archive--container--list">
			<?php cp_resources()->templates->get_template_part( "parts/filter-selected" ); ?>

			<div class="cp-resources-archive--list">
				<?php if ( have_posts() ) { ?>
					<?php while( have_posts() ) : the_post();  ?>
						<div class="cp-resources-archive--list--item" onclick="window.location = jQuery(this).find('.cp-resources-list-resource--title a').attr('href');">
							<?php cp_resources()->templates->get_template_part( 'parts/resource-list' ); ?>
						</div>
					<?php endwhile; ?>
				<?php } else if( !empty( $type ) && is_object( $type ) && !empty( $type->plural_label ) ) { ?>
						<p><?php printf( __( "No %s found.", 'cp-library' ), $type->plural_label ); ?></p>
				<?php }; ?>
			</div>
		</div>

	</div>

	<?php do_action( 'cp_resources_after_archive' ); ?>

	<?php the_posts_pagination(); ?>
</div>
