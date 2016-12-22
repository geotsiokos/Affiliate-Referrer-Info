<?php
/**
 * Plugin Name: Affiliate Referrer Info
 * Plugin URI: http://www.netpad.gr
 * Description: Use the shortcode [affiliate_referrer_info] to show your affiliates who has referred them. An extension for Affiliates Pro and Enterprise.
 * Version: 1.0
 * Author: George Tsiokos
 * Author URI: http://www.netpad.gr
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

add_action ( 'init', 'add_affiliate_ref_info_shortcodes' );

function add_affiliate_ref_info_shortcodes ( $data ) {
	add_shortcode ( 'affiliate_referrer_info', 'affiliate_referrer_info' );
}

add_action ( 'affiliates_stored_affiliate', 'affiliate_pro_referrer' );

function affiliate_pro_referrer ( $new_affiliate_id ) {
	$referrer_id = 1;
	$active_plugins = get_option( 'active_plugins', array() );	
	$affiliates_pro_is_active = in_array( 'affiliates-pro/affiliates-pro.php', $active_plugins );
	
	if ( $affiliates_pro_is_active ) {
		include_once ( ABSPATH . 'wp-content/plugins/affiliates-pro/lib/core/class-affiliates-service.php' );
		if (Affiliates_Service::get_referrer_id( $service = null ) ) {
			$referrer_id = Affiliates_Service::get_referrer_id( $service = null );
			$options = get_option( 'affiliate_referrers', array() );
		}
		$options[] = array( $referrer_id => (int)$new_affiliate_id );
		update_option( 'affiliate_referrers', $options, false );		
	}
}

function affiliate_referrer_info ( $attr = array(), $content = null ) {
	global $wpdb;
	
	$affiliate_referrer = 1;
	$active_plugins = get_option( 'active_plugins', array() );
	$affiliates_pro_is_active = in_array( 'affiliates-pro/affiliates-pro.php', $active_plugins );
	$affiliates_entr_is_active = in_array( 'affiliates-enterprise/affiliates-enterprise.php', $active_plugins );

	$options = shortcode_atts(
			array(
					'direct'  => false,
					'display' => 'user_login'
			),
			$attr
	);
	extract( $options );
	$output = '';
	$user_id = get_current_user_id();
	
	if ( $affiliates_entr_is_active ) {
		$relations_table = _affiliates_get_tablename( 'affiliates_relations' );		
	
		if ( $user_id && affiliates_user_is_affiliate( $user_id ) ) {
			if ( $affiliate_ids = affiliates_get_user_affiliate( $user_id ) ) {
				foreach ( $affiliate_ids as $affiliate_id ) {
					if ( $affiliate_referrer = $wpdb->get_var( $wpdb->prepare (	"SELECT from_affiliate_id FROM $relations_table WHERE to_affiliate_id=%d ", $affiliate_id ) ) ) {
						continue;
					}
				}
			}
		}
	} else if ( $affiliates_pro_is_active ) {
		if ( get_option( 'affiliate_referrers' ) ) {
			$affiliate_referrers = get_option( 'affiliate_referrers' );
			$relations = count( $affiliate_referrers );
			write_log( 'count referrers' );
			write_log( $relations );
			
			if ( $user_id && affiliates_user_is_affiliate( $user_id ) ) {
				if ( !is_null( affiliates_get_user_affiliate( $user_id, 'active' ) ) ) {
					$affiliate_ids = affiliates_get_user_affiliate( $user_id, 'active' );
					write_log( 'affiliate_referrers' );
					write_log( $affiliate_referrers );
					$affiliate_id = $affiliate_ids[0];					
					foreach( $affiliate_referrers as $aff_referrer ) {
						foreach( $aff_referrer as $key => $value ) {
							if ( $affiliate_id == $value ) {
								$affiliate_referrer = $key;
							}
						}
					}
				}
			}
		}
	} else {
		echo "<div class='error'>The <strong>Affiliates Referrer Info</strong> plugin requires on of the Affiliates plugins by <a href='http://itthinx.com'>Itthinx</a> to be installed and activated.</div>";
	}
	if ( $user_id = affiliates_get_affiliate_user( $affiliate_referrer ) ) {
		if ( $user = get_user_by( 'id', $user_id ) ) {
			switch( $display ) {
				case 'user_login' :
					$output .= $user->user_login;
					break;
				case 'user_nicename' :
					$output .= $user->user_nicename;
					break;
				case 'user_email' :
					$output .= $user->user_email;
					break;
				case 'user_url' :
					$output .= $user->user_url;
					break;
				case 'display_name' :
					$output .= $user->display_name;
					break;
				default :
					$output .= $user->user_login;
			}
			$output = wp_strip_all_tags( $output );
		}
	}

	return $output;
}

register_uninstall_hook( __FILE__, 'ari_uninstall' );

function ari_uninstall() {
	delete_option ( 'affiliate_referrers' );
}
?>
