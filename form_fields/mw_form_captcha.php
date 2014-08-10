<?php
/**
 * Name: MW Form Field Captcha
 * Version: 1.0.0
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : July 14, 2014
 * Modified:
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class MW_Form_Field_Captcha extends MW_Form_Field {
	private $captcha_string = null;

	/**
	 * set_names
	 * shortcode_name、display_nameを定義。各子クラスで上書きする。
	 * @return array shortcode_name, display_name
	 */
	protected function set_names() {
		return array(
			'shortcode_name' => 'mwform_captcha',
			'display_name' => __( 'CAPTCHA', MW_WP_Form_Captcha::DOMAIN ),
		);
	}

	/**
	 * setDefaults
	 * $this->defaultsを設定し返す
	 * @return array
	 */
	protected function setDefaults() {
		return array(
			'name' => MW_WP_Form_Captcha::DOMAIN,
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
			'uniqid' => $uniqid,
		) );
		if ( $this->atts['show_error'] !== 'false' )
			$_ret .= $this->getError( $this->atts['name'] );

		$_ret .= $this->uniqid_field( $uniqid );
		return $_ret;
	}

	/**
	 * confirmPage
	 * 確認ページでのフォーム項目を返す
	 * @return	String	HTML
	 */
	protected function confirmPage() {
		$value = $this->Form->getValue( $this->atts['name'] );
		$uniqid = $this->Form->getValue( MW_WP_Form_Captcha::DOMAIN . '-uniqid' );
		$_ret  = $this->Form->hidden( $this->atts['name'], $value );
		$_ret .= $this->uniqid_field( $uniqid );
		return $_ret;
	}

	/**
	 * add_mwform_tag_generator
	 * フォームタグジェネレーター
	 */
	public function mwform_tag_generator_dialog() {
		?>
		<p>
			<strong>name</strong>
			<input type="text" name="name" value="<?php echo esc_attr( $this->defaults['name'] ); ?>" />
		</p>
		<p>
			<strong><?php esc_html_e( 'Dsiplay error', MWF_Config::DOMAIN ); ?></strong>
			<input type="checkbox" name="show_error" value="false" /> <?php esc_html_e( 'Don\'t display error.', MWF_Config::DOMAIN ); ?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Convert half alphanumeric', MWF_Config::DOMAIN ); ?></strong>
			<input type="checkbox" name="conv_half_alphanumeric" value="false" /> <?php esc_html_e( 'Don\'t Convert.', MWF_Config::DOMAIN ); ?>
		</p>
		<?php
	}

	/**
	 * captcha_field
	 * captcha フィールド生成
	 * @param string $name name属性
	 * @param array
	 * @return string html
	 */
	public function captcha_field( $name, $options = array() ) {
		$defaults = array(
			'conv-half-alphanumeric' => true,
			'uniqid' => '',
		);
		$options = array_merge( $defaults, $options );

		$temp_dir = MW_WP_Form_Captcha::getTempDir();
		$temp_dir = $temp_dir['dir'];
		$filename = MW_WP_Form_Captcha::getFileName( $options['uniqid'] );

		// ディレクトリを作成
		MW_WP_Form_Captcha::createTempDir();
		MW_WP_Form_Captcha::cleanTempDir();

		// ランダムな文字列を生成
		$string = $this->makeString();

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
		$image_url = $this->createImage( $image_filepath, $string );

		$_ret  = sprintf( '<img src="%s" alt="" /><br />', $image_url );
		$_ret .= esc_html__( 'Please input the alphanumeric characters of five characters that are displayed.', MW_WP_Form_Captcha::DOMAIN );
		$_ret .= '<br />';
		$_ret .= $this->Form->text( $name, array(
			'value' => null,
			'size' => 10,
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
	 * @return string
	 */
	protected function makeString() {
		$string = '';
		$s = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$num = 5;
		for ( $i = 0; $i < $num; $i++ ) {
			$string .= substr( $s , rand( 0, strlen( $s ) - 1 ), 1 );
		}
		return $string;
	}

	/**
	 * createImage
	 * 画像を生成
	 * @param string $filepath 画像ファイルのパス
	 * @param string $string 書き込む文字列
	 * @return string 画像URL
	 */
	public function createImage( $filepath, $string ) {
		$fonts = array();
		foreach ( glob( plugin_dir_path( __FILE__ ) . '../fonts/*' ) as $font ) {
			$fonts[] = $font;
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
		for ( $i = 0; $i < 5; $i ++ ) {
			$this->imageline( $im, rand( 1, 3 ) );
		}
		for ( $i = 0; $i < 50; $i ++ ) {
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
	 * imageline
	 * ラインを画像に書き込む
	 * @param image $image イメージリソース
	 * @param int $thickness ラインの
	 */
	protected function imageline( $image, $thickness = 1 ) {
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
