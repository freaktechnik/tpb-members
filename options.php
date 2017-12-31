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
if($lastError) {
    add_settings_error(
        'tpbm_messages',
        'tpbm_sync_error',
        __('Sync error', TPBM_TEXT_DOMAIN).':'.esc_html($lastError),
        'error'
    );
}
else if($didSync) {
    add_settings_error(
        'tpbm_messages',
        'tpbm_sync_success',
        __('Synced members successfully', TPBM_TEXT_DOMAIN),
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
    <form method="POST">
        <!-- TODO needs nonce -->
        <input type="hidden" name="sync" value="sync">
        <?php submit_button(__('Sync members')) ?>
    </form>
</div>
