<?php

class TPBClient {
    const CONTACT_NUMBER = 0;
    const CONTACT_LAST_NAME = 1;
    const CONTACT_FIRST_NAME = 2;
    const CONTACT_LICENSE = 3;
    const CONTACT_EMAIL = 4;
    const CONTACT_STREET = 5;
    const CONTACT_ZIP = 6;
    const CONTACT_CITY = 7;
    const CONTACT_PHONE_P = 8;
    const CONTACT_PHONE_W = 9;
    const CONTACT_PHONE_M = 10;

    private $sessionToken;
    private $tpbSession;
    private $buchId;

    public function __construct(string $sessionToken, string $buchId = null) {
        $this->sessionToken = $sessionToken;
        $this->buchId = $buchId;
        $this->login();
    }

    private function getCookies(): string {
        $cookies = 't='.$this->sessionToken;
        if(!empty($this->tbpSession)) {
            $cookies .= ';TPBSID='.$this->tbpSession;
        }
        return $cookies;
    }

    private function get(string $url, bool $withCookies = false): string {
        $c = curl_init('https://www.teamplanbuch.ch/'.$url);
        curl_setopt($c, CURLOPT_COOKIE, $this->getCookies());
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        if($withCookies) {
            curl_setopt($c, CURLOPT_HEADER, 1);
        }
        return curl_exec($c);
    }

    private function login() {
        $tpbSessionStart = $this->get('startseite', true);
        preg_match('/TPBSID=([^;]+)/i', $tbpSessionStart, $matches);
        if(empty($matches) || empty($matches[1])) {
            throw new Exception('No Session started');
        }
        $this->tpbSession = array_shift($matches[1]);
        $this->get('dologin.php');

        if(!empty($this->buchId)) {
            $this->get('index.php?buch='.$this->buchId);
        }
    }

    /**
     * Returns an array of members. Every member is an array too:
     *   0: Number
     *   1: Last name
     *   2: First name
     *   3: License
     *   4: Email
     *   5: Street
     *   6: ZIP
     *   7: City
     *   8: Phone private
     *   9: Phone work
     *   10: Mobile phone
     * See also the CONTACT_* constants on this class.
     */
    public function getMembers(): array {
        $result = $this->get('spb/buch/listen/userliste.php?json');
        return json_decode($result, true)['aaData'];
    }
}
