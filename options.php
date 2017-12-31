<?php
if(isset($_GET['settings-updated'])) {
    // add settings saved message with the class of "updated"
    add_settings_error(
        'tpbm_messages',
        'tpbm_message',
        __( 'Settings Saved', TPBM_TEXT_DOMAIN),
        'updated'
    );
}
settings_errors('tpbm_messages');
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form method="POST" action="options.php">
        <?php
            settings_fields(TPBMembersPlugin::OPTION_GROUP);
            do_settings_sections(TPBMembersPlugin::OPTION_SLUG);
            submit_button();
        ?>
    </form>
    <!-- TODO add form to sync members list -->
</div>
