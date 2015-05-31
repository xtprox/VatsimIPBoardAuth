# VatsimIPBoardAuth
==============

Anthony Lawrence <freelancer@anthonylawrence.me.uk>

An IPBoard 4 login handler for Cert's SSO.  For IPBoard 3.4.x, look in the other branch. This does _not_ modify logins to the admin area.

## Some Important Notes (For those adding this to an existing board)

The installation steps below work on the premise that this is a _fresh_ installation of IPBoard.  If you wish to introduce this to an _existing_ V4 board, then there are some additional steps.

AFTER you've completed the installation, you should populate the vatsim_cid field with the current CID's of your membership _if you have them_.
If you do not have this data, the best suggestion would be for members to be treated as "new members" and then have administrators merge those accounts when people request it.

## Installation
0) Take a backup.  Please!
1) Clone this repository somewhere.
2) Run git submodule update --init --recursive to download the demo code.
3) Copy all files into system/Login
4) Run the following MySQL Commands to add this login handler to your installation, add a vatsim_cid field to the members table and then adds some needed entries to your languages table (English only). *_make sure that you edit the queries to match your IPBoard prefix if it isn't default_*.

```
INSERT INTO `ibf_core_login_handlers` (`login_key`, `login_settings`, `login_order`, `login_acp`, `login_enabled`) VALUES ('Vatsim', '{"sso_base":null,"sso_key":null,"sso_secret":null,"sso_rsa_key":null}', '1', '0', '1');
ALTER TABLE `ibf_core_members` ADD `vatsim_cid` INT(7) UNSIGNED NULL AFTER `member_id`;
INSERT INTO `ibf_core_sys_lang_words` (`word_id`, `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export`, `word_plugin`, `word_theme`) VALUES (NULL, '1', 'core', 'login_handler_Vatsim', 'VATSIM SSO Login', NULL, '100031', NULL, '0', '1', NULL, NULL);
INSERT INTO `ibf_core_sys_lang_words` (`word_id`, `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export`, `word_plugin`, `word_theme`) VALUES (NULL, '1', 'core', 'login_vatsim_sso_base', 'VATSIM SSO Base URL', NULL, '100031', NULL, '0', '1', NULL, NULL);
INSERT INTO `ibf_core_sys_lang_words` (`word_id`, `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export`, `word_plugin`, `word_theme`) VALUES (NULL, '1', 'core', 'login_vatsim_sso_key', 'Your SSO Key', NULL, '100031', NULL, '0', '1', NULL, NULL);
INSERT INTO `ibf_core_sys_lang_words` (`word_id`, `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export`, `word_plugin`, `word_theme`) VALUES (NULL, '1', 'core', 'login_vatsim_sso_secret', 'Your SSO Secret', NULL, '100031', NULL, '0', '1', NULL, NULL);
INSERT INTO `ibf_core_sys_lang_words` (`word_id`, `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export`, `word_plugin`, `word_theme`) VALUES (NULL, '1', 'core', 'login_vatsim_sso_rsa_key', 'Your RSA Key', NULL, '100031', NULL, '0', '1', NULL, NULL);
INSERT INTO `ibf_core_sys_lang_words` (`word_id`, `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export`, `word_plugin`, `word_theme`) VALUES (NULL, '1', 'core', 'login_vatsim_cancelled', 'You cancelled your login.', NULL, '100031', NULL, '0', '1', NULL, NULL);
```

5) Login to your Admin Control Panel, visit System > Settings > Login Handlers
6) Edit the Vatsim Login Handler and complete the form with the details requested.  You can get these from the VATSIM SSO website once you've registered as an SSO consumer.
7) Visit Customisation > Themes and click "Edit HTML/CSS" for the default theme (you'll have to figure out these steps for any custom/purchased templates)
8) Locate loginPopup in the global section and modify it to match: https://gist.github.com/A-Lawrence/d0fe1ffcf13c15e1926d (What we're achiving here is disabling the normal login for users)
9) Locate login in the system section and modify it to match: https://gist.github.com/A-Lawrence/135238e6aff855fcb571 (We're also disabling normal login from the standard login screen)
10) Save those templates (your forum may lose it's styling momentarily whils the style caches are rebuilt)
11) That's it! Go and use it.