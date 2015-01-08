<?php
/**
 * Name: MW WP Form Field Captcha
 * Version: 1.2.0
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : July 14, 2014
 * Modified: January 9, 2014
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class MW_WP_Form_Field_Captcha extends MW_WP_Form_Abstract_Form_Field {

	/**
	 * $captcha_string
	 * @var string
	 */
	private $captcha_string = null;

	/**
	 * set_names
	 * shortcode_name、display_nameを定義。各子クラスで上書きする。
	 * @return array shortcode_name, display_name
	 */
	protected function set_names() {
		return array(
			'shortcode_name' => 'mwform_captcha',
			'display_name'   => __( 'CAPTCHA', MW_WP_Form_Captcha::DOMAIN ),
		);
	}

	/**
	 * setDefaults
	 * $this->defaultsを設定し返す
	 * @return array
	 */
	protected function setDefaults() {
		return array(
			'name'       => MW_WP_Form_Captcha::DOMAIN,
			'string'     => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
			'line'       => 5,
			'scratch'    => 50,
			'show_error' => 'true',
			'conv_half_alphanumeric' => 'true',
		);
	}

	/**
	 * inputPage
	 * 入力ページでのフォーム項目を返す
	 * @return string html
	 */
	protected function inputPage() {
		$conv_half_alphanumeric = true;
		if ( $this->atts['conv_half_alphanumeric'] === 'false' ) {
			$conv_half_alphanumeric = false;
		}
		$uniqid = uniqid();

		$_ret = $this->captcha_field( $this->atts['name'], array(
			'conv-half-alphanumeric' => $conv_half_alphanumeric,
			'uniqid'  => $uniqid,
			'string'  => $this->atts['string'],
			'line'    => $this->atts['line'],
			'scratch' => $this->atts['scratch'],
		) );
		if ( $this->atts['show_error'] !== 'false' ) {
			$_ret .= $this->get_error( $this->atts['name'] );
		}

		$_ret .= $this->uniqid_field( $uniqid );
		return $_ret;
	}

	/**
	 * confirmPage
	 * 確認ページでのフォーム項目を返す
	 * @return string HTML
	 */
	protected function confirmPage() {
		$value  = $this->Form->get_raw( $this->atts['name'] );
		$uniqid = $this->Form->get_raw( MW_WP_Form_Captcha::DOMAIN . '-uniqid' );
		$_ret   = $this->Form->hidden( $this->atts['name'], $value );
		$_ret  .= $this->uniqid_field( $uniqid );
		return $_ret;
	}

	/**
	 * add_mwform_tag_generator
	 * フォームタグジェネレーター
	 */
	public function mwform_tag_generator_dialog( array $options = array() ) {
		?>
		<p>
			<strong>name</strong>
			<?php
			$name = $this->get_value_for_generator( 'name', $options );
			if ( !$name ) {
				$name = $this->defaults['name'];
			}
			?>
			<input type="text" name="name" value="<?php echo esc_attr( $name ); ?>" />
		</p>
		<p>
			<strong><?php esc_html_e( 'String to use', 'mw-wp-form-captcha' ); ?></strong>
			<?php $string = $this->get_value_for_generator( 'string', $options ); ?>
			<input type="text" name="string" value="<?php echo esc_attr( $string ); ?>" placeholder="<?php echo esc_attr( $this->defaults['string'] ); ?>" /><br />
			<span class="mwf_note"><?php esc_html_e( 'Please input at least five characters.', 'mw-wp-form-captcha' ); ?></span>
		</p>
		<p>
			<strong><?php esc_html_e( 'Number of lines', 'mw-wp-form-captcha' ); ?></strong>
			<?php $line = $this->get_value_for_generator( 'line', $options ); ?>
			<input type="text" name="line" value="<?php echo esc_attr( $line ); ?>" size="3" maxlength="2" placeholder="<?php echo esc_attr( $this->defaults['line'] ); ?>" />
		</p>
		<p>
			<strong><?php esc_html_e( 'Number of scratches', 'mw-wp-form-captcha' ); ?></strong>
			<?php $scratch = $this->get_value_for_generator( 'scratch', $options ); ?>
			<input type="text" name="scratch" value="<?php echo esc_attr( $scratch ); ?>" size="3" maxlength="2" placeholder="<?php echo esc_attr( $this->defaults['scratch'] ); ?>" />
		</p>
		<p>
			<strong><?php esc_html_e( 'Dsiplay error', MWF_Config::DOMAIN ); ?></strong>
			<?php $show_error = $this->get_value_for_generator( 'show_error', $options ); ?>
			<input type="checkbox" name="show_error" value="false" <?php checked( 'false', $show_error ); ?> /> <?php esc_html_e( 'Don\'t display error.', MWF_Config::DOMAIN ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Convert half alphanumeric', MWF_Config::DOMAIN ); ?></strong>
			<?php $conv_half_alphanumeric = $this->get_value_for_generator( 'conv_half_alphanumeric', $options ); ?>
			<input type="checkbox" name="conv_half_alphanumeric" value="false" <?php checked( 'false', $conv_half_alphanumeric ); ?> /> <?php esc_html_e( 'Don\'t Convert.', MWF_Config::DOMAIN ); ?>
		</p>
		<?php
	}

	/**
	 * captcha_field
	 * captcha フィールド生成
	 * @param string $name name属性
	 * @param array $options
	 * @return string html
	 */
	public function captcha_field( $name, $options = array() ) {
		$defaults = array(
			'conv-half-alphanumeric' => true,
			'uniqid'  => '',
			'string'  => $this->defaults['string'],
			'line'    => $this->defaults['line'],
			'scratch' => $this->defaults['scratch'],
		);
		$options = shortcode_atts( $defaults, $options );

		$temp_dir = MW_WP_Form_Captcha::getTempDir();
		$temp_dir = $temp_dir['dir'];
		$filename = MW_WP_Form_Captcha::getFileName( $options['uniqid'] );

		// ディレクトリを作成
		MW_WP_Form_Captcha::createTempDir();
		MW_WP_Form_Captcha::cleanTempDir();

		// ランダムな文字列を生成
		$string = $this->makeString( $options['string'] );

		// 答えを保存
		$answer_filepath = trailingslashit( $temp_dir ) . $filename . '.php';
		$php_string = sprintf(
			'<?php define( "MWFORM_CAPTCHA_STRING", "%s" ) ?>',
			$string
		);
		file_put_contents( $answer_filepath, $php_string );
		@chmod( $answer_filepath, 0600 );

		// 画像を保存
		$image_filepath = trailingslashit( $temp_dir ) . $filename . '.jpg';
		$image_url = $this->createImage( $image_filepath, $string, array(
			'line'    => $options['line'],
			'scratch' => $options['scratch'],
		) );

		$_ret  = sprintf( '<img src="%s" alt="" /><br />', $image_url );
		$_ret .= esc_html__( 'Please input the alphanumeric characters of five characters that are displayed.', MW_WP_Form_Captcha::DOMAIN );
		$_ret .= '<br />';
		$_ret .= $this->Form->text( $name, array(
			'value' => null,
			'size'  => 10,
			'conv-half-alphanumeric' => $options['conv-half-alphanumeric'],
		) );
		return $_ret;
	}

	/**
	 * uniqid_field
	 * @param string $uniqid salt として利用する文字列
	 * @return string hidden フィールド
	 */
	protected function uniqid_field( $uniqid ) {
		return $this->Form->hidden( MW_WP_Form_Captcha::DOMAIN . '-uniqid', $uniqid );
	}

	/**
	 * makeString
	 * ランダム文字列を生成
	 * @param string $string
	 * @return string
	 */
	protected function makeString( $string ) {
		$return = '';
		$num = 5;
		if ( strlen( $string ) < $num ) {
			$string = $this->defaults['string'];
		}
		for ( $i = 0; $i < $num; $i++ ) {
			$return .= substr( $string , rand( 0, strlen( $string ) - 1 ), 1 );
		}
		return $return;
	}

	/**
	 * createImage
	 * 画像を生成
	 * @param string $filepath 画像ファイルのパス
	 * @param string $string 書き込む文字列
	 * @param array $options
	 * @return string 画像URL
	 */
	public function createImage( $filepath, $string, array $options ) {
		$fonts = array();
		foreach ( glob( plugin_dir_path( __FILE__ ) . '../fonts/*' ) as $font ) {
			$fonts[] = $font;
		}

		$options = shortcode_atts( $this->defaults, $options );
		if ( !preg_match( '/^\d+$/', $options['line'] ) ) {
			$options['line'] = $this->defaults['line'];
		}
		if ( !preg_match( '/^\d+$/', $options['scratch'] ) ) {
			$options['scratch'] = $this->defaults['scratch'];
		}

		$im = @imagecreate( 200, 50 ) or die( 'Cannot Initialize new GD image stream.' );
		$background_color = imagecolorallocate( $im, rand( 80, 100 ), rand( 80, 100 ), rand( 80, 100 ) );

		$count = strlen( $string );
		for ( $i = 0; $i < $count; $i ++ ) {
			$font_key = array_rand( $fonts );
			$font = $fonts[$font_key];
			$text_color = imagecolorallocate( $im, rand( 0, 30 ), rand( 0, 30 ), rand( 0, 30 ) );
			$font_size = rand( 20, 24 );
			$angle = rand( -25, 25 );
			$x = ( $i + 1 ) * 25;
			$y = rand( 20, 40 );
			$value = substr( $string, $i, 1 );
			imagettftext( $im, $font_size, $angle, $x, $y, $text_color, $font, $value );
		}
		for ( $i = 0; $i < $options['line']; $i ++ ) {
			$this->line( $im, rand( 1, 3 ) );
		}
		for ( $i = 0; $i < $options['scratch']; $i ++ ) {
			$this->scratch( $im );
		}
		imagejpeg( $im, $filepath );
		imagedestroy( $im );

		$temp_dir = MW_WP_Form_Captcha::getTempDir();
		$image_url = str_replace( $temp_dir['dir'], $temp_dir['url'], $filepath );
		return $image_url;
	}

	/**
	 * scratch
	 * スクラッチを画像に書き込む
	 * @param image $image イメージリソース
	 */
	protected function scratch( $image ) {
		$color = imagecolorallocate( $image, rand( 0, 30 ), rand( 0, 30 ), rand( 0, 30 ) );
		$x1 = rand( 0, 200 );
		$y1 = rand( 0, 50 );
		$x2 = $x1 + rand( -10, 10 );
		$y2 = $y1 + rand( -10, 10 );
		imageline( $image, $x1, $y1, $x2, $y2, $color );
	}

	/**
	 * line
	 * ラインを画像に書き込む
	 * @param image $image イメージリソース
	 * @param int $thickness ラインの
	 */
	protected function line( $image, $thickness = 1 ) {
		$color = imagecolorallocate( $image, rand( 0, 30 ), rand( 0, 30 ), rand( 0, 30 ) );
		$x1 = rand( 0, 200 );
		$y1 = 0;
		if ( $x1 === 0 ) {
			$y1 = rand( 0, 50 );
		}
		$x2 = 200;
		if ( $x1 !== 0 ) {
			$x2 = rand( 0, 200 );
		}
		$y2 = 50;
		if ( $y1 !== 0 ) {
			$y2 = rand( 0, 50 );
		}
		for ( $i = 0; $i < $thickness; $i ++ ) {
			if ( $x1 === 0 ) {
				// 下にずらす
				imageline( $image, $x1, $y1 + $i, $x2, $y2 + $i, $color );
			} else {
				// 右にずらす
				imageline( $image, $x1 + $i, $y1, $x2 + $i, $y2, $color );
			}
		}
	}
}
