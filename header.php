<?php
/**
 * Theme header template.
 *
 * @package TailPress
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class('antialiased'); ?>>
<?php do_action('tailpress_site_before'); ?>
<div id="page" class="min-h-screen flex flex-col">
    <?php do_action('tailpress_header'); ?>

    <header class="fixed left-0 right-0 top-0 z-40 bg-white shadow-sm transition-all duration-300">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <div class="shrink-0">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="text-xl font-bold">
                        <?php bloginfo('name'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="flex-grow">
                <?php get_template_part('template-parts/nav-menu'); ?>
            </div>
        </div>
    </header>

    <div id="content" class="site-content grow">
        <?php do_action('tailpress_content_start'); ?>
        <main>
