<?php
/**
 * Base spike theme template.
 *
 * @package TtcSpikeBase
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<main id="ttc-spike-template" data-theme="base" data-stylesheet="<?php echo esc_attr( get_stylesheet() ); ?>" data-template="<?php echo esc_attr( get_template() ); ?>">
	<?php
	while ( have_posts() ) {
		the_post();
		the_title( '<h1>', '</h1>' );
		the_content();
	}
	?>
</main>
<?php wp_footer(); ?>
</body>
</html>
