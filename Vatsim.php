<?php

namespace IPS\Login;

class _Vatsim extends LoginAbstract
{
    /**
     * @brief    Icon
     */
    public static $icon = 'lock';

    /**
     * Get Form
     *
     * @param    \IPS\Http\Url $url The URL for the login page
     * @param    bool          $ucp If this is being done from the User CP
     *
     * @return    string
     */
    public function loginForm($url, $ucp = false)
    {
        $redirectUrl = \IPS\Http\Url::internal("login/?loginProcess=vatsim", "none");
        return "<a href='$redirectUrl' type='submit' class='ipsButton ipsButton_primary'>VATSIM SSO</a>";
    }

    /**
     * Authenticate
     *
     * @param    string      $url    The URL for the login page
     * @param    \IPS\Member $member If we want to integrate this login method with an existing member, provide the
     *                               member object
     *
     * @return    \IPS\Member
     * @throws    \IPS\Login\Exception
     */
    public function authenticate($url, $member = null)
    {
        if ($member !== null) {
            return $member;
        }

        try {
            // Let's do the VATSIM SSO Stuff
            require_once "VatsimSSO/OAuth.php";
            require_once "VatsimSSO/SSO.class.php";

            $ssoRequest = new \IPS\Login\VatsimSSO\SSO($this->settings["sso_base"], $this->settings["sso_key"], $this->settings["sso_secret"], "RSA", $this->settings["sso_rsa_key"]);
            $ssoReturn = \IPS\Http\Url::internal("index.php?login&loginProcess=vatsim&remember_me=1&return=true", "none");

            // Deal with the return!
            if(isset(\IPS\Request::i()->return)){

                // Cancelled
                if(isset(\IPS\Request::i()->oauth_cancel)){
                    \IPS\Output::i()->error( 'login_vatsim_cancelled', 'vs1001', 408, '' );
                    return;
                }

                // Fine?
                if(isset(\IPS\Request::i()->oauth_verifier)){
                    if(isset(\IPS\Request::i()->cookie['sso_session_oauth']) && isset(\IPS\Request::i()->cookie['sso_session_oauth_key']) && isset(\IPS\Request::i()->cookie['sso_session_oauth_secret'])){
                        if(@\IPS\Request::i()->oauth_token != \IPS\Request::i()->cookie['sso_session_oauth_key']){
                            throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
                        }

                        if(@!isset(\IPS\Request::i()->oauth_verifier)){
                            throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
                        }

                        // Get the user details!
                        $member = $ssoRequest->checkLogin(\IPS\Request::i()->cookie['sso_session_oauth_key'], \IPS\Request::i()->cookie['sso_session_oauth_secret'], @\IPS\Request::i()->oauth_verifier);

                        if(!$member){
                            throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
                        }

                        $ssoMember = $member->user;

                        // HOUSTON, WE have the data!
                        if ($ssoMember && isset($ssoMember->id)) {
                            /* Try to find member */
                            $member = \IPS\Login\VatsimMember::load($ssoMember->id, 'vatsim_cid');

                            /* If we don't have one, create one */
                            if (!$member->member_id) {
                                /* Create member */
                                $member = new \IPS\Login\VatsimMember;
                                $member->member_group_id = \IPS\Settings::i()->member_group;
                            }

                            // We have one! Let's update.
                            $member->vatsim_cid = $ssoMember->id;
                            $member->name = $ssoMember->name_first." ".$ssoMember->name_last;
                            $member->email = isset($ssoMember->email) ? $ssoMember->email : NULL;
                            $member->save();
                            return $member;
                        }
                        // We shouldn't get here.
                        throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
                    }
                }
            }

            // Let's deal with the token request and send them packing for a bit.

            $token = $ssoRequest->requestToken($ssoReturn, false, false);

            if($token){

                \IPS\Request::i()->setcookie('sso_session_oauth','tstval',\IPS\DateTime::ts( time()+648000));
                \IPS\Request::i()->setcookie('sso_session_oauth_key',(string) $token->token->oauth_token,\IPS\DateTime::ts( time()+648000));
                \IPS\Request::i()->setcookie('sso_session_oauth_secret',(string) $token->token->oauth_token_secret,\IPS\DateTime::ts( time()+648000));
                $ssoRequest->sendToVatsim();
                return;
            }

            // We should never get here
            throw new \IPS\Login\Exception('generic_error', \IPS\Login\Exception::INTERNAL_ERROR);
        } catch (\IPS\Http\Request\Exception $e) {
            throw new \IPS\Login\Exception('generic_error', \IPS\Login\Exception::INTERNAL_ERROR);
        }
    }

    /**
     * Link Account
     *
     * @param    \IPS\Member $member  The member
     * @param    mixed       $details Details as they were passed to the exception thrown in authenticate()
     *
     * @return    void
     */
    public static function link( \IPS\Member $member, $details )
    {
        return;
    }

    /**
     * ACP Settings Form
     *
     * @param    string $url URL to redirect user to after successful submission
     *
     * @return    array    List of settings to save - settings will be stored to core_login_handlers.login_settings DB
     *                     field
     * @code
    return array( 'savekey'    => new \IPS\Helpers\Form\[Type]( ... ), ... );
     * @endcode
     */
    public function acpForm()
    {
        \IPS\Output::i()->sidebar['actions'] = array(
            'help'	=> array(
                'title'		=> 'help',
                'icon'		=> 'question-circle',
                'link'		=> \IPS\Http\Url::external( 'http://forums.vatsim.net/viewtopic.php?f=134&t=65133' ),
                'target'	=> '_blank',
                'class'		=> ''
            ),
        );

        return array(
            'sso_base'		=> new \IPS\Helpers\Form\Text( 'login_vatsim_sso_base', ( isset( $this->settings['sso_base'] ) ) ? $this->settings['sso_base'] : '', TRUE ),
            'sso_key'		=> new \IPS\Helpers\Form\Text( 'login_vatsim_sso_key', ( isset( $this->settings['sso_key'] ) ) ? $this->settings['sso_key'] : '', TRUE ),
            'sso_secret'	=> new \IPS\Helpers\Form\Password( 'login_vatsim_sso_secret', ( isset( $this->settings['sso_secret'] ) ) ? $this->settings['sso_secret'] : '', TRUE ),
            'sso_rsa_key'	=> new \IPS\Helpers\Form\TextArea( 'login_vatsim_sso_rsa_key', ( isset( $this->settings['sso_rsa_key'] ) ) ? $this->settings['sso_rsa_key'] : '', TRUE )
        );
    }

    /**
     * Can a member change their email/password with this login handler?
     *
     * @param    string      $type   'email' or 'password'
     * @param    \IPS\Member $member The member
     *
     * @return    bool
     */
    public function canChange($type, \IPS\Member $member)
    {
        return false;
    }

    public function canProcess(\IPS\Member $member)
    {
        return false;
    }
}
