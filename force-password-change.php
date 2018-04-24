<?php
/*
Plugin Name:  WooCommerce Force Password Change
Description:  Require customers to change their password on first login.
Version:      0.7
License:      GPL v2 or later
Plugin URI:   https://github.com/jhob101/wp-force-password-change
Author:       John Hobson
Author URI:   https://damselflycreative.com
Text Domain:  force-password-change
Domain Path:  /languages/



	About this plugin
	-----------------

	Based on plugin by Simon Blackbourn (https://twitter.com/lumpysimon, simon@lumpylemon.co.uk)
	This plugin redirects newly-registered customers to the My Account > My Profile page when they first log in.
	Until they have changed their password, they will not be able to access the rest of the site.
	A WooCommerce notice is also displayed informing them that they must change their password.

	License
	-------

	Copyright (c) Lumpy Lemon Ltd. All rights reserved.

	Released under the GPL license:
	http://www.opensource.org/licenses/gpl-license.php

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
*/


$force_password_change = new force_password_change;

class force_password_change {
	// just a bunch of functions called from various hooks
	function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'user_register', array( $this, 'registered' ) );
		add_action( 'woocommerce_save_account_details', array( $this, 'updated' ), 10, 1 );
		add_action( 'template_redirect', array( $this, 'redirect' ) );
		add_action( 'current_screen', array( $this, 'redirect' ) );
		add_action( 'init', array( $this, 'notice' ) );
	}

	// load localisation files
	function init() {
		load_plugin_textdomain(
			'force-password-change',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}


	// add a user meta field when a new user is registered
	function registered( $user_id ) {
		$user = get_userdata( $user_id );
		if ( in_array( 'customer', (array) $user->roles ) ) {
			add_user_meta( $user_id, 'force-password-change', 1 );
		}

	}

	// delete the user meta field when a user successfully changes their password
	function updated( $user_id ) {
		if ( get_user_meta( $user_id, 'force-password-change', true ) ) {
			$pass1 = ! empty( $_POST['password_1'] ) ? $_POST['password_1'] : '';
			$pass2 = ! empty( $_POST['password_2'] ) ? $_POST['password_2'] : '';

			if ( $pass1 != $pass2 or empty( $pass1 ) or empty( $pass2 ) or false !== strpos( stripslashes( $pass1 ), "\\" ) ) {
				return;
			}

			delete_user_meta( $user_id, 'force-password-change' );
			wc_clear_notices();
			wc_add_notice( __( 'Password changed successfully.', 'woocommerce' ) );
			wp_safe_redirect( site_url() );
			exit;
		}
	}

	// if:
	// - we're logged in,
	// - the user meta field is present,
	// - we're on the front-end or any admin screen apart from the edit profile page or plugins page,
	// then redirect to the edit profile page
	function redirect() {
		global $current_user;

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( is_wc_endpoint_url( 'edit-account' ) || is_wc_endpoint_url( 'customer-logout' ) ) {
			return;
		}

		wp_get_current_user();

		if ( get_user_meta( $current_user->ID, 'force-password-change', true ) ) {
			//wp_redirect( wc_get_endpoint_url( 'edit-account' ) );
			wp_redirect( site_url() . '/my-account/edit-account/' );
			exit; // never forget this after wp_redirect!
		}
	}

	// if the user meta field is present, display an admin notice
	function notice() {
		global $current_user;
		wp_get_current_user();

		if ( get_user_meta( $current_user->ID, 'force-password-change', true ) ) {
			// Prevent double notices
			wc_clear_notices();
			wc_add_notice( __( 'Please change your password in order to continue using this website.', 'force-password-change' ) );
		}

	}
} // class
