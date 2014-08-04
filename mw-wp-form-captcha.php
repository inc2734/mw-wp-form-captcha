<?php
/**
 * Plugin Name: MW WP Form Captcha
 * Plugin URI: http://plugins.2inc.org/mw-wp-form/
 * Description: Adding captcha field on MW WP Form.
 * Version: 1.0.0
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : August 4, 2014
 * Modified:
 * Text Domain: mw-wp-form-captcha
 * Domain Path: /languages/
 * License: GPLv2
 */
class MW_WP_Form_Captcha {

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
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_filter( 'mwform_validation_rules', array( $this, 'validation_captcha') );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * plugins_loaded
	 */
	public function plugins_loaded() {
		if ( class_exists( 'MW_WP_Form' ) ) {
			load_plugin_textdomain( self::DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );

			include_once( plugin_dir_path( __FILE__ ) . 'form_fields/mw_form_captcha.php' );
			include_once( plugin_dir_path( __FILE__ ) . 'validation_rules/mw_validation_rule_captcha.php' );
			include_once( plugin_dir_path( __FILE__ ) . 'modules/plugin-update.php' );

			new mw_form_field_captcha();
			new ATPU_Plugin( 'http://plugins.2inc.org/mw-wp-form/api/', 'mw-wp-form-captcha' );
		}
	}

	/**
	 * validation_captcha
	 * captcha バリデーションを追加
	 * @param array $validation_rules
	 * @return array $validation_rules
	 */
	public function validation_captcha( $validation_rules ) {
		$validation_rules['captcha'] = 'mw_validation_rule_captcha';
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
		@chmod( $temp_dir, 0700 );
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