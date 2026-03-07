<?php
/**
 * Hero Block Template (Example)
 *
 * @package TailPress
 */

$hero_image     = get_sub_field('hero_image');
$hero_heading   = get_sub_field('hero_heading');
$hero_subheading = get_sub_field('hero_subheading');
$hero_cta_text  = get_sub_field('hero_cta_text');
$hero_cta_url   = get_sub_field('hero_cta_url');
$overlay_opacity = get_sub_field('overlay_opacity') ?: 40;
$text_alignment = get_sub_field('hero_text_alignment') ?: 'center';
$hero_height    = get_sub_field('hero_height') ?: 'large';

$hero_height_css = wm_get_hero_height_css($hero_height);

$alignment_classes = [
    'center' => 'text-center justify-center',
    'left'   => 'text-left justify-start',
    'right'  => 'text-right justify-end',
];
$align_class = $alignment_classes[$text_alignment] ?? $alignment_classes['center'];

$hero_id = 'hero-' . uniqid();
?>

<?php if ($hero_image && $hero_heading): ?>
<style>
#<?php echo $hero_id; ?> .hero-container {
    <?php echo $hero_height_css; ?>
}
</style>

<section class="relative" id="<?php echo $hero_id; ?>">
    <div class="hero-container relative w-full bg-gray-800">
        <?php if ($hero_image): ?>
            <img
                src="<?php echo esc_url(wp_get_attachment_image_url($hero_image['id'], 'hero-large')); ?>"
                alt="<?php echo esc_attr($hero_image['alt'] ?: $hero_heading); ?>"
                class="absolute top-0 left-0 w-full h-full object-cover"
                fetchpriority="high"
                loading="eager"
                style="z-index: 1;"
            />
        <?php endif; ?>

        <div
            class="absolute inset-0 bg-black"
            style="opacity: <?php echo ($overlay_opacity / 100); ?>; z-index: 2;"
        ></div>

        <div class="relative h-full" style="z-index: 10;">
            <div class="container mx-auto px-6 h-full flex items-center <?php echo esc_attr($align_class); ?>">
                <div class="text-white max-w-4xl <?php echo $text_alignment === 'center' ? 'mx-auto' : ''; ?>">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold mb-5">
                        <?php echo esc_html($hero_heading); ?>
                    </h1>

                    <?php if ($hero_subheading): ?>
                        <p class="mt-5 text-base sm:text-lg md:text-xl text-white/90">
                            <?php echo nl2br(esc_html($hero_subheading)); ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($hero_cta_text && $hero_cta_url): ?>
                        <div class="mt-8">
                            <a
                                href="<?php echo esc_url($hero_cta_url); ?>"
                                class="inline-block bg-primary-500 hover:bg-primary-600 text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all duration-300 hover:shadow-lg"
                            >
                                <?php echo esc_html($hero_cta_text); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
