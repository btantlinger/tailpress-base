<?php
/**
 * 404 template.
 *
 * @package TailPress
 */

get_header();
?>

<div class="container mx-auto px-6 py-32 text-center">
    <h1 class="text-5xl font-bold mb-4">404</h1>
    <p class="text-xl text-gray-500 mb-8">Page not found.</p>
    <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-block bg-primary-500 text-white px-6 py-3 rounded-lg hover:bg-primary-600 transition-colors">
        Go Home
    </a>
</div>

<?php get_footer(); ?>
