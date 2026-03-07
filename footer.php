<?php
/**
 * Theme footer template.
 *
 * @package TailPress
 */
?>
        </main>
        <?php do_action('tailpress_content_end'); ?>
    </div>
    <?php do_action('tailpress_content_after'); ?>

    <footer class="bg-gray-900 text-white py-12" role="contentinfo">
        <div class="container mx-auto px-6">
            <?php do_action('tailpress_footer'); ?>
            <div class="grid md:grid-cols-3 gap-8 mb-8 pb-8 border-b border-gray-700">
                <div>
                    <?php if (has_custom_logo()) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <h3 class="text-xl font-bold mb-4"><?php bloginfo('name'); ?></h3>
                    <?php endif; ?>
                    <p class="text-gray-400 text-sm mt-4"><?php bloginfo('description'); ?></p>
                </div>
                <?php if (has_nav_menu('footer_1')) : ?>
                <div>
                    <?php wp_nav_menu(['theme_location' => 'footer_1', 'container' => false, 'menu_class' => 'space-y-2 text-sm text-gray-400', 'depth' => 1]); ?>
                </div>
                <?php endif; ?>
                <?php if (has_nav_menu('footer_2')) : ?>
                <div>
                    <?php wp_nav_menu(['theme_location' => 'footer_2', 'container' => false, 'menu_class' => 'space-y-2 text-sm text-gray-400', 'depth' => 1]); ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="text-center text-sm text-gray-500">
                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.
            </div>
        </div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
