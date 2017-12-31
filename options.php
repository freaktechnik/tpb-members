<div class="wrap">
    <h1>TPB Members Options</h1>
    <form method="POST" action="options.php">
        <?php
            settings_fields(TPBMembersPlugin::OPTION_GROUP);
            do_settings_sections(TPBMembersPlugin::OPTION_GROUP);
            submit_button();
        ?>
    </form>
    <!-- TODO add form to sync members list -->
</div>
