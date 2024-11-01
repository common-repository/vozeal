<div class="wrap">
    <h2>Configure Vozeal</h2>
    <form method="post" action="options.php"> 
        <?php @settings_fields('intube-group'); ?>
        <?php @do_settings_fields('intube-group'); ?>

        <?php do_settings_sections('intube'); ?>

        <?php @submit_button(); ?>
    </form>
</div>
