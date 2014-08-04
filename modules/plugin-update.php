<?php
/**
 * Name: ATPU_Plugin
 * Description:
 * Version: 1.0.2
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : February 18, 2014
 * Modified: July 29, 2014
 * Package: MW Automatic Theme Plugin Update
 *
 * Original Author: jeremyclark13, Kaspars Dambis (kaspars@konstruktors.com)
 * https://github.com/jeremyclark13/automatic-theme-plugin-update
 *
 * License: GPLv2
 *
 * Copyright 2014 Takashi Kitajima (email : inc@2inc.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
class ATPU_Plugin {
	private $api_url;
	private $plugin_slug;

	public function __construct( $api_url = '', $plugin_slug = '' ) {
		if ( !$api_url ) {
			die( 'Please set $api_url.' );
		}
		$this->api_url = esc_url( $api_url );

		if ( !$plugin_slug ) {
			die( 'Please set $plugin_slug.' );
		}
		$this->plugin_slug = $plugin_slug;

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_plugin_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_api_call' ), 10, 3 );
	}

	public function check_for_plugin_update( $checked_data ) {
		global $wp_version;
		$plugin_name = $this->plugin_slug . '/' . $this->plugin_slug . '.php';

		if ( !empty( $checked_data->checked ) &&
			 is_array( $checked_data->checked ) &&
			 isset( $checked_data->checked[$plugin_name] ) ) {

			$args = array(
				'slug' => $this->plugin_slug,
				'version' => $checked_data->checked[$plugin_name],
			);
			$request_string = array(
				'body' => array(
					'action' => 'basic_check',
					'request' => serialize( $args ),
					'api-key' => md5( home_url() ),
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(),
			);
			$raw_response = wp_remote_post( $this->api_url, $request_string );
			if ( !is_wp_error( $raw_response ) && $raw_response['response']['code'] == 200 && is_serialized( $raw_response['body'] ) ) {
				$response = unserialize( $raw_response['body'] );
			}

			if ( isset( $response ) && is_object( $response ) && !empty( $response ) ) {
				$checked_data->response[$this->plugin_slug . '/' . $this->plugin_slug . '.php'] = $response;
			} else {
				unset( $checked_data->response[$this->plugin_slug . '/' . $this->plugin_slug . '.php'] );
			}
		}
		return $checked_data;
	}

	public function plugin_api_call( $def, $action, $args ) {
		global $wp_version;
		if ( !isset( $args->slug ) || $args->slug != $this->plugin_slug )
			return false;

		$plugin_info = get_site_transient( 'update_plugins' );
		$current_version = $plugin_info->checked[$this->plugin_slug . '/' . $this->plugin_slug . '.php'];
		$args->version = $current_version;

		$request_string = array(
			'body' => array(
				'action' => $action,
				'request' => serialize( $args ),
				'api-key' => md5( home_url() ),
			),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(),
		);
		$request = wp_remote_post( $this->api_url, $request_string );

		if ( is_wp_error( $request ) ) {
			$error_message = __( 'An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>' );
			$res = new WP_Error( 'plugins_api_failed', $error_message, $request->get_error_message() );
		} else {
			$res = unserialize( $request['body'] );
			if ( $res === false ) {
				$res = new WP_Error( 'plugins_api_failed', __( 'An unknown error occurred' ), $request['body'] );
			}
		}

		return $res;
	}
}
