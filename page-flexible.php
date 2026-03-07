<?php
/**
 * Template Name: Flexible Content Page
 *
 * @package TailPress
 */

get_header();
?>

<?php while (have_posts()) : the_post(); ?>
    <?php if (have_rows('page_content')): ?>
        <?php while (have_rows('page_content')): the_row(); ?>
            <?php
                $layout = get_row_layout();
                get_template_part('template-parts/blocks/' . str_replace('_', '-', $layout));
            ?>
        <?php endwhile; ?>
    <?php endif; ?>
<?php endwhile; ?>

<?php get_footer(); ?>
