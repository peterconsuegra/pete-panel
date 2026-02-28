<?php
/*
Plugin Name: Laravel Integration by WordPressPete
Plugin URI: https://wordpresspete.com
Description: Get the best of both worlds by integrating WordPress with Laravel – a powerful MVC framework for PHP. This plugin integrates WordPress with Laravel in an optimal way, while preserving the WordPress core intact.
Author: Ozone Group
Author URI: https://ozonegroup.co
Version: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


# OK, so we rock up and setup a constant....
define('ROOT_COOKIE', '/' );

# Then we paste the WP functions from /wp-includes/pluggable.php
# ...
# and to finish we replace COOKIEPATH, PLUGINS_COOKIE_PATH  and ADMIN_COOKIE_PATH with ROOT_COOKIE, job done!

if ( !function_exists('wp_set_auth_cookie') ) :
/**
 * Sets the authentication cookies based User ID.
 *
 * The $remember parameter increases the time that the cookie will be kept. The
 * default the cookie is kept without remembering is two days. When $remember is
 * set, the cookies will be kept for 14 days or two weeks.
 *
 * @since 2.5
 *
 * @param int $user_id User ID
 * @param bool $remember Whether to remember the user
 */
	function wp_set_auth_cookie($user_id, $remember = false, $secure = '') {
		if ( $remember ) {
			$expiration = $expire = time() + apply_filters('auth_cookie_expiration', 1209600, $user_id, $remember);
		} else {
			$expiration = time() + apply_filters('auth_cookie_expiration', 172800, $user_id, $remember);
			$expire = 0;
		}
		
		if ( '' === $secure )
			$secure = is_ssl();		

	if ( $secure ) {
		$auth_cookie_name = SECURE_AUTH_COOKIE;
		$scheme = 'secure_auth';
	} else {
		$auth_cookie_name = AUTH_COOKIE;
		$scheme = 'auth';
	}

	$auth_cookie = wp_generate_auth_cookie($user_id, $expiration, $scheme);
	$logged_in_cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in');

	do_action('set_auth_cookie', $auth_cookie, $expire, $expiration, $user_id, $scheme);
	do_action('set_logged_in_cookie', $logged_in_cookie, $expire, $expiration, $user_id, 'logged_in');
	
	$subdomain = get_option('rootcookie_subdomain');
	$rootcookie_subdomain_manual = get_option('rootcookie_subdomain_manual');

	if($subdomain==1)
		{
			# Use Scotts implementation
			$info = get_bloginfo('url');
			$info = parse_url($info);
			$info = $info['host'];
			$exp = explode('.',$info);
			if(count($exp)==3){$domain = '.'.$exp[1].'.'.$exp[2];}
			elseif(count($exp)==2){$domain = '.'.$info;}
			elseif(3<count($exp)){$exp = array_reverse($exp); $domain = '.'.$exp[1].'.'.$exp[0];}
			else{$domain = COOKIE_DOMAIN;}
		}
	elseif (!is_null($rootcookie_subdomain_manual))
                {
			# Use manual domain name setting
                        $domain = $rootcookie_subdomain_manual;
                }
	else
		{
			# Default
			$domain = COOKIE_DOMAIN;
	}

	setcookie($auth_cookie_name, $auth_cookie, $expire, ROOT_COOKIE, $domain, $secure, true);
	/** Duplicate of above - Created by Find & Replace
	setcookie($auth_cookie_name, $auth_cookie, $expire, ROOT_COOKIE, $domain, $secure, true);
	 **/
	setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, ROOT_COOKIE, $domain, $secure_logged_in_cookie, true);
	if ( COOKIEPATH != SITECOOKIEPATH )
		setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true);
}

endif;

if ( !function_exists('wp_clear_auth_cookie') ) :
/**
 * Removes all of the cookies associated with authentication.
 *
 * @since 2.5
 */
function wp_clear_auth_cookie() {
	do_action('clear_auth_cookie');
	
	$subdomain = get_option('rootcookie_subdomain');
	$rootcookie_subdomain_manual = get_option('rootcookie_subdomain_manual');

	# As ABOVE!
	if($subdomain==1)
		{
			$info = get_bloginfo('url');
			$info = parse_url($info);
			$info = $info['host'];
			$exp = explode('.',$info);
			if(count($exp)==3){$domain = '.'.$exp[1].'.'.$exp[2];}
			elseif(count($exp)==2){$domain = '.'.$info;}
			elseif(3<count($exp)){$exp = array_reverse($exp); $domain = '.'.$exp[1].'.'.$exp[0];}
			else{$domain = COOKIE_DOMAIN;}
		}
	elseif (!is_null($rootcookie_subdomain_manual)) 
		{
			$domain = $rootcookie_subdomain_manual;
		}
	else
		{
			$domain = COOKIE_DOMAIN;
	}

	/** Clear All possible cookies **/

	setcookie(AUTH_COOKIE, ' ', time() - 31536000, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
	setcookie(AUTH_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, COOKIE_DOMAIN);
	setcookie(AUTH_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, $domain);
	setcookie(AUTH_COOKIE, ' ', time() - 31536000, ADMIN_COOKIE_PATH, $domain);
	
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, COOKIE_DOMAIN);
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, $domain);
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, ADMIN_COOKIE_PATH, $domain);

	setcookie(AUTH_COOKIE, ' ', time() - 31536000, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN);
	setcookie(AUTH_COOKIE, ' ', time() - 31536000, PLUGINS_COOKIE_PATH, $domain);
	
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN);
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, PLUGINS_COOKIE_PATH, $domain);
	
	setcookie(LOGGED_IN_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
	setcookie(LOGGED_IN_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, COOKIE_DOMAIN);
	setcookie(LOGGED_IN_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, $domain);
	setcookie(LOGGED_IN_COOKIE, ' ', time() - 31536000, COOKIEPATH, $domain);
	
	setcookie(LOGGED_IN_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
	setcookie(LOGGED_IN_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, $domain);
	
	// Old cookies
	setcookie(AUTH_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
	setcookie(AUTH_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, COOKIE_DOMAIN);
	setcookie(AUTH_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, $domain);
	setcookie(AUTH_COOKIE, ' ', time() - 31536000, COOKIEPATH, $domain);
	
	setcookie(AUTH_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
	setcookie(AUTH_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, $domain);

	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, COOKIE_DOMAIN);
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, $domain);
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, COOKIEPATH, $domain);
	
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
	setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, $domain);
	
	// Even older cookies
	setcookie(USER_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
	setcookie(USER_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, COOKIE_DOMAIN);
	setcookie(USER_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, $domain);
	setcookie(USER_COOKIE, ' ', time() - 31536000, COOKIEPATH, $domain);
	
	setcookie(PASS_COOKIE, ' ', time() - 31536000, COOKIEPATH, COOKIE_DOMAIN);
	setcookie(PASS_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, COOKIE_DOMAIN);
	setcookie(PASS_COOKIE, ' ', time() - 31536000, ROOT_COOKIE, $domain);
	setcookie(PASS_COOKIE, ' ', time() - 31536000, COOKIEPATH, $domain);
	
	setcookie(USER_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
	setcookie(USER_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, $domain);

	setcookie(PASS_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN);
	setcookie(PASS_COOKIE, ' ', time() - 31536000, SITECOOKIEPATH, $domain);

	
}
endif;

function rootcookie_activate ()
	{
		$opt_val = get_option('rootcookie_subdomain');
		if($opt_val!=1)
			{
				delete_option('rootcookie_subdomain');
				add_option('rootcookie_subdomain',0);
			}
	}
function rootcookie_menu ()
	{
		global $rootcookie_admin_hook;
		
		$rootcookie_admin_hook = add_options_page('root Cookie Options', 'Laravel Integration ', 'manage_options' , 'root-cookie', 'rootcookie_options');
	}

function rootcookie_options ()
	{
	
		// Read in existing option value from database
		$rootcookie_subdomain_on = get_option('rootcookie_subdomain');
		$rootcookie_subdomain_manual = get_option('rootcookie_subdomain_manual');
		$rootcookie_donate = get_option('rootcookie_donate');

		$checked=false;
		if($rootcookie_subdomain_on==1){$checked=true;}
		
		$donate=false;
		if($rootcookie_donate==1){$donate=true;}
	
		// See if the user has posted us some information
		// If they did, this hidden field will be set to 'Y'
		if( $_POST['rootcookie_submit_hidden'] == 'Y' )
			{
				if(isset($_POST['rootcookie_subdomain']))
					{
						# This enables the guessing that the domain written by Scott
						$rootcookie_subdomain_on = 1;
						$checked=true;
					} else {
						$rootcookie_subdomain_on = 0;
                                                $checked=false;

						# Implement  a manual domain method for .co.uk or .co.jp etc
						if(isset($_POST['rootcookie_subdomain_manual'])) {
							$rootcookie_subdomain_manual = $_POST['rootcookie_subdomain_manual'];
							update_option('rootcookie_subdomain_manual', $rootcookie_subdomain_manual );
						}


					}
				
				if(isset($_POST['rootcookie_donate'])) {
					
					$rootcookie_donate = 1;
					$donate=true;
					update_option('rootcookie_donate', $rootcookie_donate );
				}

				
				update_option('rootcookie_subdomain', $rootcookie_subdomain_on );
				echo '<div class="updated"><p><strong>'._('Options saved.').'</strong></p></div>';

				// Re-Read Val so Form Prints Correctly.
				$rootcookie_subdomain_on = get_option('rootcookie_subdomain');
				$rootcookie_subdomain_manual = get_option('rootcookie_subdomain_manual');
			}
?>
<div class="wrap">
<?php echo "<h2>" . __( 'Laravel Integration by WordPressPete', 'rootcookie_trans_domain' ) . "</h2>"; ?>
<i>Get the best of both worlds by integrating WordPress with Laravel – a powerful MVC framework for PHP.</i>
<form name="plugin_options" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
<input type="hidden" name="rootcookie_submit_hidden" value="Y" />

<p>

<table class="form-table">

<tr valign="top">
	<th scope="row"><?php _e("Allow Cookies to go across All Subdomains:", 'rootcookie_trans_domain' ); ?> </th>
	<td><input type="checkbox" name="rootcookie_subdomain" value="<?php echo $rootcookie_subdomain_on; ?>"<?php if($checked){echo " CHECKED";} ?> /><span class="description">Tick this box to activate the WordPress Laravel Integration.</span></td>
</tr>


</table>

</p>
<p class="submit">
<input class="button-primary" type="submit" name="Submit" value="<?php _e('Update Options', 'rootcookie_trans_domain' ) ?>" />
</p>
<hr />
</form>

</div>
<?php
	}

// Run all actions and hooks at the end to keep it tidy
	
	if (is_admin()) { // only run admin stuff if we're an admin.
		add_action('admin_menu', 'rootcookie_menu');
	}
	
register_activation_hook( __FILE__,'rootcookie_activate');
?>
