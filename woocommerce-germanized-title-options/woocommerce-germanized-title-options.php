<?php
/*
Plugin Name: WooCommerce Germanized Title Options
Plugin URI:
Description: Option(s) to not use accusative in addresses for the male title and have the title in a new line before the name.
Version: 1.0
Author: Thore Janke (thore@homepage-helden.de)
Author URI: https://www.homepage-helden.de/
License:
License URI:
*/

/* germanized support ticket: https://vendidero.de/tickets/anrede-in-der-pdf-rechnung */

add_action('plugins_loaded', 'WC_Germanized_Title_Options_init');

function WC_Germanized_Title_Options_init() {
	if ( class_exists('WC_GZD_Checkout') ) {
		class WC_Germanized_Title_Options extends WC_GZD_Checkout
		{
			public function set_formatted_address( $placeholder, $args ) {
				if ( ! WC_GZD_Customer_Helper::instance()->is_customer_title_enabled() ) {
					return $placeholder;
				}

			$woocommerce_germanized_title_options_options = get_option( 'woocommerce_germanized_title_options_option_name' ); // Array of All Options
			if(isset($woocommerce_germanized_title_options_options['akkusativ_fr_die_mnnliche_anrede_verwenden_0'])):
 				$akkusativ_fr_die_mnnliche_anrede_verwenden_0 = $woocommerce_germanized_title_options_options['akkusativ_fr_die_mnnliche_anrede_verwenden_0']; // Akkusativ für die männliche Anrede verwenden?
			else:
 				$akkusativ_fr_die_mnnliche_anrede_verwenden_0 = false;
 			endif;
			if(isset($woocommerce_germanized_title_options_options['neue_zeile_nach_der_anrede_1'])):
 				$neue_zeile_nach_der_anrede_1 = $woocommerce_germanized_title_options_options['neue_zeile_nach_der_anrede_1']; // Neue Zeile nach der Anrede?
 			else:
 				$neue_zeile_nach_der_anrede_1 = false;
 			endif;

				if ( isset( $args['title'] ) ) {
					if ( '' !== $args['title'] ) {
						$title = wc_gzd_get_customer_title( $args['title'] );

						/**
						 * Ugly hack to force accusative in addresses
						 */
						if ( __( 'Mr.', 'woocommerce-germanized' ) === $title ) {
							if($woocommerce_germanized_title_options_options && $akkusativ_fr_die_mnnliche_anrede_verwenden_0 == 'akkusativ_fr_die_mnnliche_anrede_verwenden_0'):
								$title = $title;
							else:
								$title = _x( 'Mr.', 'customer-title-male-address', 'woocommerce-germanized' );
							endif;
						}

						$args['title'] = $title;
					}

					$placeholder['{title}']       = $args['title'];
					$placeholder['{title_upper}'] = strtoupper( $args['title'] );

					if (strpos($placeholder['{name}'], '{title}') === false) {
                        $useNewLine = $woocommerce_germanized_title_options_options && $neue_zeile_nach_der_anrede_1 == 'neue_zeile_nach_der_anrede_1';
                        $useAkkusativ = $woocommerce_germanized_title_options_options && $akkusativ_fr_die_mnnliche_anrede_verwenden_0 == 'akkusativ_fr_die_mnnliche_anrede_verwenden_0';

                        $nameReplacement = str_replace(_x('Mr.', 'customer-title-male-address', 'woocommerce-germanized') . ' ', '', $placeholder['{name}']);
                        $titleReplacement = str_replace(_x('Mr.', 'customer-title-male-address', 'woocommerce-germanized') . ' ', '', $placeholder['{title}']);

                        $placeholder['{name}'] = $placeholder['{title}'] . ($useNewLine ? '<br>' : ' ') . $nameReplacement;
                        $placeholder['{name_upper}'] = $placeholder['{title_upper}'] . ($useNewLine ? '<br>' : ' ') . $nameReplacement;

                        if (!$useNewLine && $useAkkusativ) {
                            $placeholder['{name}'] = $placeholder['{title}'] . ' ' . $nameReplacement;
                            $placeholder['{name_upper}'] = $placeholder['{title_upper}'] . ' ' . $nameReplacement;
                        }
                    }

				}

				return $placeholder;
			}
		}
		$WC_Germanized_Title_Options = new WC_Germanized_Title_Options();
	} else {
		add_action('admin_notices', 'wcgzd_not_loaded');
	}
}

function wcgzd_not_loaded() {
    printf(
      '<div class="error"><p>%s</p></div>',
      __('WooCommerce Germanized is not activated')
    );
}


class WooCommerceGermanizedTitleOptions {
	private $woocommerce_germanized_title_options_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'woocommerce_germanized_title_options_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'woocommerce_germanized_title_options_page_init' ) );
	}

	public function woocommerce_germanized_title_options_add_plugin_page() {
		add_menu_page(
			'WooCommerce Germanized Title Options', // page_title
			'WooCommerce Germanized Title Options', // menu_title
			'manage_options', // capability
			'woocommerce-germanized-title-options', // menu_slug
			array( $this, 'woocommerce_germanized_title_options_create_admin_page' ), // function
			'dashicons-admin-settings', // icon_url
			25 // position
		);
	}

	public function woocommerce_germanized_title_options_create_admin_page() {
		$this->woocommerce_germanized_title_options_options = get_option( 'woocommerce_germanized_title_options_option_name' ); ?>

		<div class="wrap">
			<h2>WooCommerce Germanized Title Options</h2>
			<p></p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'woocommerce_germanized_title_options_option_group' );
					do_settings_sections( 'woocommerce-germanized-title-options-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function woocommerce_germanized_title_options_page_init() {
		register_setting(
			'woocommerce_germanized_title_options_option_group', // option_group
			'woocommerce_germanized_title_options_option_name', // option_name
			array( $this, 'woocommerce_germanized_title_options_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'woocommerce_germanized_title_options_setting_section', // id
			'Settings', // title
			array( $this, 'woocommerce_germanized_title_options_section_info' ), // callback
			'woocommerce-germanized-title-options-admin' // page
		);

		add_settings_field(
			'akkusativ_fr_die_mnnliche_anrede_verwenden_0', // id
			'Akkusativ für die männliche Anrede ausschalten?', // title
			array( $this, 'akkusativ_fr_die_mnnliche_anrede_verwenden_0_callback' ), // callback
			'woocommerce-germanized-title-options-admin', // page
			'woocommerce_germanized_title_options_setting_section' // section
		);

		add_settings_field(
			'neue_zeile_nach_der_anrede_1', // id
			'Neue Zeile nach der Anrede?', // title
			array( $this, 'neue_zeile_nach_der_anrede_1_callback' ), // callback
			'woocommerce-germanized-title-options-admin', // page
			'woocommerce_germanized_title_options_setting_section' // section
		);
	}

	public function woocommerce_germanized_title_options_sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['akkusativ_fr_die_mnnliche_anrede_verwenden_0'] ) ) {
			$sanitary_values['akkusativ_fr_die_mnnliche_anrede_verwenden_0'] = $input['akkusativ_fr_die_mnnliche_anrede_verwenden_0'];
		}

		if ( isset( $input['neue_zeile_nach_der_anrede_1'] ) ) {
			$sanitary_values['neue_zeile_nach_der_anrede_1'] = $input['neue_zeile_nach_der_anrede_1'];
		}

		return $sanitary_values;
	}

	public function woocommerce_germanized_title_options_section_info() {

	}

	public function akkusativ_fr_die_mnnliche_anrede_verwenden_0_callback() {
		printf(
			'<input type="checkbox" name="woocommerce_germanized_title_options_option_name[akkusativ_fr_die_mnnliche_anrede_verwenden_0]" id="akkusativ_fr_die_mnnliche_anrede_verwenden_0" value="akkusativ_fr_die_mnnliche_anrede_verwenden_0" %s> <label for="akkusativ_fr_die_mnnliche_anrede_verwenden_0">Statt \'Herrn\' nur \'Herr\'</label>',
			( isset( $this->woocommerce_germanized_title_options_options['akkusativ_fr_die_mnnliche_anrede_verwenden_0'] ) && $this->woocommerce_germanized_title_options_options['akkusativ_fr_die_mnnliche_anrede_verwenden_0'] === 'akkusativ_fr_die_mnnliche_anrede_verwenden_0' ) ? 'checked' : ''
		);
	}

	public function neue_zeile_nach_der_anrede_1_callback() {
		printf(
			'<input type="checkbox" name="woocommerce_germanized_title_options_option_name[neue_zeile_nach_der_anrede_1]" id="neue_zeile_nach_der_anrede_1" value="neue_zeile_nach_der_anrede_1" %s>',
			( isset( $this->woocommerce_germanized_title_options_options['neue_zeile_nach_der_anrede_1'] ) && $this->woocommerce_germanized_title_options_options['neue_zeile_nach_der_anrede_1'] === 'neue_zeile_nach_der_anrede_1' ) ? 'checked' : ''
		);
	}

}
if ( is_admin() )
	$woocommerce_germanized_title_options = new WooCommerceGermanizedTitleOptions();
