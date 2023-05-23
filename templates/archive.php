<?php
use CP_Resources\Templates;

$description = get_the_archive_description();
?>

<div class="cp-resources-archive">

	<?php do_action( 'cp_resources_before_archive' ); ?>

	<h1 class="page-title"><?php echo apply_filters( 'cp-resources-archive-title', cp_resources()->setup->post_types->resource->plural_label ); ?></h1>

	<div class="cpl-archive--container">

		<div class="cpl-archive--container--filter">
			<?php cp_resources()->templates->get_template_part( "parts/filter" ); ?>
		</div>

		<div class="cpl-archive--container--list">
			<?php  cp_resources()->templates->get_template_part( "parts/filter-selected" ); ?>

			<div class="cpl-archive--list">
				<?php if ( have_posts() ) { ?>
					<?php while( have_posts() ) : the_post();  ?>
						<div class="cpl-archive--list--item">
							<?php the_title(); // Templates::get_template_part( "parts/" . Templates::get_type() . "-list" ); ?>
						</div>
					<?php endwhile; ?>
				<?php } else if( !empty( $type ) && is_object( $type ) && !empty( $type->plural_label ) ) { ?>
						<p><?php printf( __( "No %s found.", 'cp-library' ), $type->plural_label ); ?></p>
				<?php }; ?>
			</div>
		</div>

	</div>

	<?php do_action( 'cp_resources_after_archive' ); ?>
</div>
