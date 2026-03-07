<?php
/**
 * Single post template.
 *
 * @package TailPress
 */

get_header();
?>

<?php while (have_posts()) : the_post(); ?>
<article <?php post_class('container mx-auto px-6 py-16'); ?>>
    <h1 class="text-3xl font-bold mb-6"><?php the_title(); ?></h1>
    <div class="prose max-w-none">
        <?php the_content(); ?>
    </div>
</article>
<?php endwhile; ?>

<?php get_footer(); ?>
