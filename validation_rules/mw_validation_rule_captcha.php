<?php
/**
 * Name: MW Validation Rule Captcha
 * Description: 値がアルファベット
 * Version: 1.0.0
 * Author: Takashi Kitajima
 * Author URI: http://2inc.org
 * Created : July 21, 2014
 * Modified:
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class MW_Validation_Rule_Captcha extends MW_Validation_Rule {

	/**
	 * バリデーションルール名を指定
	 */
	protected static $name = 'captcha';

	/**
	 * rule
	 * @param string $key name属性
	 * @param array $option
	 * @return string エラーメッセージ
	 */
	public function rule( $key, array $options = array() ) {
		$value = $this->Data->get( $key );

		// 値が存在しないけど他のデータもない（=フォーム送信自体されていない）ときはエラーではない
		if ( is_null( $value ) && !$this->Data->getValues() ) {
			return;
		}

		$temp_dir = MW_WP_Form_Captcha::getTempDir();
		$temp_dir = $temp_dir['dir'];
		$uniqid = $this->Data->get( MW_WP_Form_Captcha::DOMAIN . '-uniqid' );
		$filename = MW_WP_Form_Captcha::getFileName( $uniqid );
		$answer_filepath = trailingslashit( $temp_dir ) . $filename . '.php';
		if ( is_readable( $answer_filepath ) )
			include_once( $answer_filepath );
		$answer = '';
		if ( defined( 'MWFORM_CAPTCHA_STRING' ) )
			$answer = MWFORM_CAPTCHA_STRING;

		if ( $answer !== $value || empty( $answer ) ) {
			$defaults = array(
				'message' => __( 'Invalid', MW_WP_Form_Captcha::DOMAIN )
			);
			$options = array_merge( $defaults, $options );
			return $options['message'];
		}
	}

	/**
	 * admin
	 * @param numeric $key バリデーションルールセットの識別番号
	 * @param array $value バリデーションルールセットの内容
	 */
	public static function admin( $key, $value ) {
		?>
		<label><input type="checkbox" <?php checked( $value[self::getName()], 1 ); ?> name="<?php echo MWF_Config::NAME; ?>[validation][<?php echo $key; ?>][<?php echo esc_attr( self::getName() ); ?>]" value="1" /><?php esc_html_e( 'CAPTCHA', MW_WP_Form_Captcha::DOMAIN ); ?></label>
		<?php
	}
}