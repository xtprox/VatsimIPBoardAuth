<?php

class ssoPublicSessions extends publicSessions {

    public function __construct() {
        /* Finish loading publicSessions constructor */
        parent::__construct();

        /* If we are already logged in, or we are a bot, just skip */
        if (self::$data_store['member_id'] OR $this->_member->is_not_human) {
            return;
        }

        /**
         * Below is our custom function to check if we're logged in.
         * At this point, only guests are being checked, due to our
         * check above for a member id
         * */
        $member = $this->checkSession();

        if (!is_array($member)) {
            return false;
        }


        self::setMember($member['member_id']);

        $this->_updateMemberSession();
        
        return true;
    }

    public function checkSession() {
        if (!isset($_GET['ssoAuth'])) {
            if (!isset($_GET['app'], $_GET['module'], $_GET['section'])) {
                return;
            } elseif ($_GET["app"] != "core" && $_GET['module'] != "global" && $_GET['section'] != "login") {
                return;
            }
        }

        // Include the SSO pages.
        require("./vatsimSso/OAuth.php");
        require("./vatsimSso/SSO.class.php");
        require("./vatsimSso/config.php");
        session_start();

        // Start the SSO class.
        $SSO = new SSO($sso['base'], $sso['key'], $sso['secret'], $sso['method'], $sso['cert']);
        $sso_return = $sso['return'];

        if (isset($_GET['ssoAuth']) && isset($_GET['oauth_verifier']) && !isset($_GET['oauth_cancel'])) {
            // check to make sure there is a saved token for this user
            if (isset($_SESSION[SSO_SESSION]) && isset($_SESSION[SSO_SESSION]['key']) && isset($_SESSION[SSO_SESSION]['secret'])) {
                if (@$_GET['oauth_token'] != $_SESSION[SSO_SESSION]['key']) {
                    return;
                }

                if (@!isset($_GET['oauth_verifier'])) {
                    return;
                }

                // obtain the details of this user from VATSIM
                $user = $SSO->checkLogin($_SESSION[SSO_SESSION]['key'], $_SESSION[SSO_SESSION]['secret'], @$_GET['oauth_verifier']);

                if ($user) {
                    // One-time use of tokens, token no longer valid
                    unset($_SESSION[SSO_SESSION]);

                    $details = $user->user;

                    // We now have the details, let's check whether they exist!
                    $_member = $this->DB->buildAndFetch(
                            array(
                                'select' => 'member_id',
                                'from' => 'members',
                                'where' => 'name=' . $details->id,
                            )
                    );

                    /* If the member is not found in our members table, we need to create a new member record */
                    if (!$_member) {
                        $member = IPSMember::create(
                                        array(
                                    'members' => array(
                                        'name' => $details->id,
                                        'members_display_name' => $details->name_first . " " . $details->name_last,
                                        'password' => sha1(uniqid),
                                        'email' => $details->email,
                                    ),
                                        ), false, true, false
                        );
                        return $member;
                    } else {
                        /* If the member IS found, we need to load them and return them to be logged in */
                        IPSMember::save($_member["member_id"], array('members' => array(
                                'email' => $details->email,
                                'members_display_name' => $details->name_first . " " . $details->name_last,
                        )));
                        $member = IPSMember::load($_member['member_id']);
                        return $member;
                    }

                    return;
                } else {
                    return;
                }
            }
        }

        // Do the "login" part.
        $token = $SSO->requestToken($sso_return, false, false);
        if ($token) {
            // store the token information in the session so that we can retrieve it when the user returns
            $_SESSION[SSO_SESSION] = array(
                'key' => (string) $token->token->oauth_token, // identifying string for this token
                'secret' => (string) $token->token->oauth_token_secret // secret (password) for this token. Keep server-side, do not make visible to the user
            );

            // redirect the member to VATSIM
            $SSO->sendToVatsim();
        } else {
            return;
        }
    }

}
