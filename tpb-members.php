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

require_once 'TPBClient.php';

class TPBMembersPlugin {
    const OPTION_PREFIX = 'tpbm_';
    const OPTION_GROUP = 'tpbm-settings';

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

    public function __construct() {
        if(is_admin()) {
            add_action('admin_init', [self::class, 'onInit']);
            add_action('admin_menu', [self::class, 'onMenu']);
        }
        add_shortcode('tbp-members', [$this, 'makeList']);
    }

    public static function onInit() {
        register_setting(self::OPTION_GROUP, self::OPTION_PREFIX.'token', [
            'type' => 'string'
        ]);
        register_setting(self::OPTION_GROUP, self::OPTION_PREFIX.'buch', [
            'type' => 'string',
            'default' => null
        ]);
    }

    public static function onMenu() {
        add_menu_page('TPB Members', 'TPB Mitgliederliste', 'manage_options', 'tpbm-list', [self::class, 'showOptions']);
    }

    public static function showOptions() {
        include 'options.php';
    }

    private function getOption(string $name, $default) {
        return get_option(self::OPTION_PREFIX.$name, $default);
    }

    private function setOption(string $name, $value) {
        update_option(self::OPTION_PREFIX.$name, $value);
    }

    private function syncMembers() {
        $tpbToken = $this->getOption('token');
        $tpbBuch = $this->getOption('buch', NULL);
        $client = new TPBClient($tpbToken, $tpbBuch);
        $membersList = $client->getMembers();
        $this->setOption('members', json_encode($membersList));
    }

    /**
     * Makes a members list based on a shortcode like [tpb-members columns="first,last"]
     */
    public function makeList($attributes): string {
        $settings = shortcode_atts([
            'columns' => 'first,last'
        ], $attributes);

        $members = json_decode($this->getOption('members', '[]'), true);
        if(empty($members)) {
            return '<p>Keine Mitglieder!</p>';
        }

        $columns = array_map(function($i) {
            return trim($i);
        }, explode(',', $settings['columns']));
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
        return $html;
    }
}

new TPBMembersPlugin();
