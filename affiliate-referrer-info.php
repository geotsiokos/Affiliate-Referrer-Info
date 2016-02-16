<?php
/**
 * Plugin Name: Affiliate Referrer Info
 * Plugin URI: http://www.netpad.gr
 * Description: Use the shortcode [affiliate-referrer-info] to show your affiliates who has referred them. An extension for Affiliates Enterprise.
 * Version: 1.0
 * Author: George Tsiokos
 * Author URI: http://www.netpad.gr
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

add_action ( 'init', 'add_affiliate_ref_info_shortcodes' );

function add_affiliate_ref_info_shortcodes ( $data ) {
	add_shortcode ( 'affiliate-referrer-info', 'affiliate_referrer_info' );
}

function affiliate_referrer_info ( $attr = array(), $content = null ) {
	global $wpdb;

	$options = shortcode_atts(
			array(
					'direct'  => false,
					'display' => 'user_login'
			),
			$attr
	);
	extract( $options );
	$output = '';
	$relations_table = _affiliates_get_tablename( 'affiliates_relations' );
	$user_id = get_current_user_id();

	if ( $user_id && affiliates_user_is_affiliate( $user_id ) ) {
		if ( $affiliate_ids = affiliates_get_user_affiliate( $user_id ) ) {
			foreach ( $affiliate_ids as $affiliate_id ) {
				if ( $affiliate_referrer = $wpdb->get_var( $wpdb->prepare (	"SELECT from_affiliate_id FROM $relations_table WHERE to_affiliate_id=%d ", $affiliate_id ) ) ) {
					continue;
				}
			}
		}
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
?>
