<?php
/*
 * Template Name: ProjectSigurd
 */
?>

<?php get_template_part('template-parts/header', '');
 ?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <h1><?php the_field('project_name'); ?></h1>
    </header>

    <div class="entry-content">
        <p><?php the_field('project_tags'); ?></p>

        <p><?php the_field('project_summary_short'); ?></p>

        <p><?php the_field('project_summary'); ?></p>

        <?php
        // Display post content

        // Display a specific ACF field (replace 'field_name' with your ACF field key)
        // $acf_field_value = get_field('field_name'); // For example, a text field
        // if ($acf_field_value) {
        //     echo '<p>' . esc_html($acf_field_value) . '</p>';
        // }

        // // Example for displaying an image field
        $acf_image = get_field('project_image');
        if ($acf_image) {
            echo '<img src="' . esc_url($acf_image['url']) . '" alt="' . esc_attr($acf_image['alt']) . '" />';
        }
        ?>
    </div>

    <footer class="entry-footer">
        <?php the_tags('<span class="tag-links">', '', '</span>'); ?>
    </footer>
</article>

<?php get_template_part('footer');; ?>
