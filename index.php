<?php
/**
 * Default template.
 *
 * @package TailPress
 */

get_header();
?>

<div class="container mx-auto px-6 py-16">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class('mb-12'); ?>>
                <h2 class="text-2xl font-bold mb-4">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>
                <div class="prose max-w-none">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <p>No content found.</p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
