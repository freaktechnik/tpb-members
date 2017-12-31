<?php
/*
Plugin Name: Teamplanbuch Members
Description: Show a members list based on Teamplanbuch
Version: 1.0.0
Author: Martin Giger
Author URI: https://humanoids.be
License: MIT
Text-Domain: tpb-members
*/

define('TPBM_TEXT_DOMAIN', 'tpb-members');

require_once __DIR__.'/TPBClient.php';

class TPBMembersPlugin {
    const OPTION_PREFIX = 'tpbm_';
    const OPTION_GROUP = 'tpbm-settings';
    const OPTION_SLUG = 'tpb-members';

    const ATTR_TO_INDEX = [
        'first' => TPBClient::CONTACT_FIRST_NAME,
        'last' => TPBClient::CONTACT_LAST_NAME,
        'email' => TPBClient::CONTACT_EMAIL,
        'street' => TPBClient::CONTACT_STREET,
        'zip' => TPBClient::CONTACT_ZIP,
        'city' => TPBClient::CONTACT_CITY,
        'phonep' => TPBClient::CONTACT_PHONE_P,
        'phonew' => TPBClient::CONTACT_PHONE_W,
        'phonem' => TPBClient::CONTACT_PHONE_M,
    ];

    const COLUMN_NAMES = [
        'first' => 'Vorname',
        'last' => 'Nachname',
        'email' => 'E-Mail',
        'street' => 'Strasse',
        'zip' => 'PLZ',
        'city' => 'Ort',
        'phonep' => 'Telefon (Privat)',
        'phonew' => 'Telefon (Arbeit)',
        'phonem' => 'Telefon (Mobil)',
    ];

    const SYNC_TASK = 'tpbm_sync';

    public function __construct() {
        if(is_admin()) {
            add_action('admin_init', [$this, 'onInit']);
            add_action('admin_menu', [$this, 'onMenu']);
        }
        add_shortcode('tpbmembers', [$this, 'makeList']);

        if(!wp_next_scheduled(self::SYNC_TASK)) {
            wp_schedule_event(time(), 'daily', self::SYNC_TASK);
        }
        add_action(self::SYNC_TASK, [$this, 'onSync']);
    }

    public function onInit() {
        register_setting(self::OPTION_GROUP, self::OPTION_PREFIX.'token', [
            'type' => 'string',
            'default' => '',
            'description' => __('TPB Token', TPBM_TEXT_DOMAIN)
        ]);
        register_setting(self::OPTION_GROUP, self::OPTION_PREFIX.'buch', [
            'type' => 'string',
            'default' => null,
            'description' => __('TPB Book ID', TPBM_TEXT_DOMAIN)
        ]);

        add_settings_section(
            self::OPTION_GROUP.'_tpb',
            __('Teamplanbuch Login', TPBM_TEXT_DOMAIN),
            function() {},
            self::OPTION_SLUG
        );

        add_settings_field(
            self::OPTION_PREFIX.'token',
            __('TPB Token', TPBM_TEXT_DOMAIN),
            [$this, 'makeInput'],
            self::OPTION_SLUG,
            self::OPTION_GROUP.'_tpb',
            [
                'name' => 'token'
            ]
        );

        add_settings_field(
            self::OPTION_PREFIX.'buch',
            __('TPB Book ID', TPBM_TEXT_DOMAIN),
            [$this, 'makeInput'],
            self::OPTION_SLUG,
            self::OPTION_GROUP.'_tpb',
            [
                'name' => 'buch'
            ]
        );
    }

    public function makeInput($args) {
        $optionName = self::OPTION_PREFIX.$args['name'];
        echo '<input name="'.$optionName.'" value="'.esc_attr($this->getOption($args['name'], '')).'" type="text">';
    }

    public function onMenu() {
        add_menu_page(
            __('TPB Member List Settings', TPBM_TEXT_DOMAIN),
            __('TPB Member List', TPBM_TEXT_DOMAIN),
            'manage_options',
            self::OPTION_SLUG,
            [$this, 'showOptions']
        );
    }

    public function onSync() {
        $this->syncMembers();
    }

    public function showOptions() {
        if($_POST['sync'] === 'sync') {
            $this->syncMembers();
            $didSync = true;
        }
        $lastError = $this->getOption('last_exception', false);
        include __DIR__.'/options.php';
    }

    private function getOption(string $name, $default) {
        return get_option(self::OPTION_PREFIX.$name, $default);
    }

    private function setOption(string $name, $value) {
        update_option(self::OPTION_PREFIX.$name, $value);
    }

    private function syncMembers() {
        $tpbToken = $this->getOption('token', '');
        $tpbBuch = $this->getOption('buch', NULL);
        try {
            $client = new TPBClient($tpbToken, $tpbBuch);
            $membersList = $client->getMembers();
            $this->setOption('members', json_encode($membersList));
            $this->setOption('last_exception', '');
        }
        catch(Exception $e) {
            $this->setOption('last_exception', $e->getMessage());
        }

    }

    /**
     * Makes a members list based on a shortcode like [tpbmembers columns="first,last"]
     */
    public function makeList($attributes): string {
        $settings = shortcode_atts([
            'columns' => 'first,last',
            'list' => ''
        ], $attributes);

        $members = json_decode($this->getOption('members', '[]'), true);
        if(empty($members)) {
            return '<p>Keine Mitglieder!</p>';
        }

        $columns = array_map(function($i) {
            return trim($i);
        }, explode(',', $settings['columns']));
        if(empty($settings['list'])) {
            $html = '<table><thead>';
            foreach($columns as $c) {
                $html .= '<th>'.self::COLUMN_NAMES[$c].'</th>';
            }
            $html .= '</thead><tbody>';
            foreach($members as $m) {
                $html .= '<tr>';
                foreach($columns as $c) {
                    $html .= '<td>'.$m[self::ATTR_TO_INDEX[$c]].'</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        else {
            $html = '<ul>';
            foreach($members as $m) {
                $html .= '<li>';
                foreach($columns as $c) {
                    $html .= $m[self::ATTR_TO_INDEX[$c]].' ';
                }
                $html .= '</li>';
            }
        }
        return $html;
    }
}

new TPBMembersPlugin();
