<?php
/**
 * Plugin Name: MW WP Form CAPTCHA
 * Plugin URI: http://plugins.2inc.org/mw-wp-form/
 * Description: Adding CAPTCHA field on MW WP Form. This plugin needs MW WP Form version 2.2.0 or later.
 * Version: 1.4.0
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : August 10, 2014
 * Modified: August 30, 2019
 * Text Domain: mw-wp-form-captcha
 * Domain Path: /languages/
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class MW_WP_Form_Captcha {

	/**
	 * PREFIX
	 */
	const PREFIX = 'mwf-captcha-';

	/**
	 * DOMAIN
	 */
	const DOMAIN = 'mw-wp-form-captcha';

	/**
	 * uninstall
	 * アンインストールした時の処理
	 */
	public static function uninstall() {
		self::removeTempDir();
	}

	/**
	 * __construct
	 */
	public function __construct() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'mw-wp-form/mw-wp-form.php' ) ) {
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
			add_filter( 'mwform_validation_rules', array( $this, 'validation_captcha'), 10, 2 );
			register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
		}
	}

	/**
	 * plugins_loaded
	 */
	public function plugins_loaded() {
		load_plugin_textdomain( self::DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );
		include_once( plugin_dir_path( __FILE__ ) . 'form-fields/class.captcha.php' );
		include_once( plugin_dir_path( __FILE__ ) . 'validation-rules/class.captcha.php' );
		new MW_WP_Form_Field_Captcha();

		if ( !class_exists( 'ATPU_Plugin' ) ) {
			include_once( plugin_dir_path( __FILE__ ) . 'modules/plugin-update.php' );
		}
		new ATPU_Plugin( 'http://plugins.2inc.org/mw-wp-form/api/', 'mw-wp-form-captcha' );
	}

	/**
	 * validation_captcha
	 * captcha バリデーションを追加
	 * @param array $validation_rules
	 * @param string $key フォーム識別子
	 * @return array $validation_rules
	 */
	public function validation_captcha( $validation_rules, $key ) {
		$validation_rules['captcha'] = new MW_WP_Form_Validation_Rule_Captcha( $key );
		return $validation_rules;
	}

	/**
	 * createTempDir
	 * Tempディレクトリを作成
	 * @return bool
	 */
	public static function createTempDir() {
		$temp_dir = self::getTempDir();
		$temp_dir = $temp_dir['dir'];
		if ( !file_exists( $temp_dir ) && !is_writable( $temp_dir ) ) {
			$_ret = wp_mkdir_p( trailingslashit( $temp_dir ) );
		} else {
			$_ret = true;
		}
		@chmod( $temp_dir, apply_filters( self::PREFIX . 'directory-permission', 0705 ) );
		return $_ret;
	}

	/**
	 * getTempDir
	 * Tempディレクトリ名（パス、URL）を返す。ディレクトリの存在可否は関係なし
	 * @return  Array  ( dir => Tempディレクトリのパス, url => Tempディレクトリのurl )
	 */
	public static function getTempDir() {
		$wp_upload_dir = wp_upload_dir();
		$temp_dir_name = '/' . self::DOMAIN . '_uploads';
		$temp_dir['dir'] = realpath( $wp_upload_dir['basedir'] ) . $temp_dir_name;
		$temp_dir['url'] = $wp_upload_dir['baseurl'] . $temp_dir_name;
		return $temp_dir;
	}

	/**
	 * removeTempDir
	 * Tempディレクトリを削除
	 * @param string $sub_dir サブディレクトリ名
	 */
	public function removeTempDir( $sub_dir = '' ) {
		$temp_dir = self::getTempDir();
		$temp_dir = $temp_dir['dir'];
		if ( $sub_dir )
			$temp_dir = trailingslashit( $temp_dir ) . $sub_dir;

		if ( !file_exists( $temp_dir ) )
			return;
		$handle = opendir( $temp_dir );
		if ( $handle === false )
			return;

		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( $file !== '.' && $file !== '..' ) {
				if ( is_dir( trailingslashit( $temp_dir ) . $file ) ) {
					self::removeTempDir( $file );
				} else {
					unlink( trailingslashit( $temp_dir ) . $file );
				}
			}
		}
		closedir( $handle );
		rmdir( $temp_dir );
	}

	/**
	 * cleanTempDir
	 * Tempディレクトリ内のファイルを削除
	 */
	public static function cleanTempDir() {
		$temp_dir = self::getTempDir();
		$temp_dir = $temp_dir['dir'];
		if ( !file_exists( $temp_dir ) )
			return;
		$handle = opendir( $temp_dir );
		if ( $handle === false )
			return;
		while ( false !== ( $filename = readdir( $handle ) ) ) {
			if ( $filename !== '.' && $filename !== '..' ) {
				if ( !is_dir( trailingslashit( $temp_dir ) . $filename ) ) {
					$stat = stat( trailingslashit( $temp_dir ) . $filename );
					if ( $stat['mtime'] + 60 * 5 < time() )
						unlink( trailingslashit( $temp_dir ) . $filename );
				}
			}
		}
		closedir( $handle );
	}

	/**
	 * getFileName
	 * @param string $uniqid salt として利用する文字列
	 * @return string 画像・回答のファイル名のベースとなる文字列
	 */
	public static function getFileName( $uniqid ) {
		return sha1( wp_create_nonce( MW_WP_Form_Captcha::DOMAIN . $uniqid ) );
	}
}
$MW_WP_Form_Captcha = new MW_WP_Form_Captcha();
