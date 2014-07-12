VS_SSO_ipBoard
==============

Anthony Lawrence <freelancer@anthonylawrence.me.uk>


ipBoard SSO integration for Cert's SSO.

1) Clone this repository somewhere.
2) Run git submodule update --init --recursive to download the demo code.
3) Copy all files into the root of your ipBoard installation (keeping the directory structure in-tact).

Edit the SSO settings in vatsimSSO/config.php, as per your guidance from the SSO developers.

When users click the login button, they'll be taken straight to the SSO authentication.  When they return, if they have an account with you already they'll be logged in, otherwise an account will be created for them.