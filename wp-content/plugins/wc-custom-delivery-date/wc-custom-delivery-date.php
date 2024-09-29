<?php
/*
Plugin Name: WP Custom Delivery Date
Description: Добавляет возможность выбора даты доставки при оформлении заказа в WooCommerce с настройкой дней доставки для категорий и продуктов.
Version: 2.0
Author: BugMaker
*/

if (!defined('ABSPATH')) {
	exit; // Защита от прямого доступа
}

// Добавляем настройки в админку для категорий и продуктов
add_action('woocommerce_product_options_general_product_data', 'wp_custom_delivery_date_product_settings');
add_action('woocommerce_process_product_meta', 'wp_custom_delivery_date_save_product_settings');

function wp_custom_delivery_date_product_settings() {
	echo '<div class="options_group">';
	woocommerce_wp_text_input(array(
		'id' => '_delivery_days',
		'label' => __('Available Delivery Days', 'woocommerce'),
		'description' => __('Comma-separated list of available days for delivery (e.g. Monday, Wednesday, Friday).', 'woocommerce'),
		'desc_tip' => 'true',
		'placeholder' => 'Monday, Wednesday, Friday'
	));
	echo '</div>';
}

function wp_custom_delivery_date_save_product_settings($post_id) {
	$delivery_days = sanitize_text_field($_POST['_delivery_days']);
	update_post_meta($post_id, '_delivery_days', $delivery_days);
}

// Настройки для категорий
add_action('product_cat_add_form_fields', 'wp_custom_delivery_date_category_add_field');
add_action('product_cat_edit_form_fields', 'wp_custom_delivery_date_category_edit_field', 10, 2);
add_action('created_product_cat', 'wp_custom_delivery_date_save_category_field');
add_action('edited_product_cat', 'wp_custom_delivery_date_save_category_field');

function wp_custom_delivery_date_category_add_field() {
	?>
    <div class="form-field">
        <label for="delivery_days"><?php _e('Available Delivery Days', 'woocommerce'); ?></label>
        <input type="text" name="delivery_days" id="delivery_days" value="" placeholder="Monday, Wednesday, Friday">
        <p class="description"><?php _e('Comma-separated list of available days for delivery.', 'woocommerce'); ?></p>
    </div>
	<?php
}

function wp_custom_delivery_date_category_edit_field($term, $taxonomy) {
	$delivery_days = get_term_meta($term->term_id, 'delivery_days', true);
	?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="delivery_days"><?php _e('Available Delivery Days', 'woocommerce'); ?></label></th>
        <td>
            <input type="text" name="delivery_days" id="delivery_days" value="<?php echo esc_attr($delivery_days); ?>" placeholder="Monday, Wednesday, Friday">
            <p class="description"><?php _e('Comma-separated list of available days for delivery.', 'woocommerce'); ?></p>
        </td>
    </tr>
	<?php
}

function wp_custom_delivery_date_save_category_field($term_id) {
	if (isset($_POST['delivery_days']) && '' !== $_POST['delivery_days']) {
		update_term_meta($term_id, 'delivery_days', sanitize_text_field($_POST['delivery_days']));
	}
}

// Добавляем календарь на страницу оформления заказа
add_action('woocommerce_after_order_notes', 'wp_custom_delivery_date_field');

function wp_custom_delivery_date_field($checkout) {
	echo '<div id="custom_delivery_date_field"><h2>' . __('Delivery Date') . '</h2>';
	woocommerce_form_field('custom_delivery_date', array(
		'type'        => 'text',
		'class'       => array('form-row-wide'),
		'label'       => __('Choose a delivery date'),
		'placeholder' => __('Select a date'),
		'required'    => true,
	), $checkout->get_value('custom_delivery_date'));
	echo '</div>';
}

// Валидация выбранной даты
add_action('woocommerce_checkout_process', 'wp_custom_delivery_date_field_process');

function wp_custom_delivery_date_field_process() {
	if (!$_POST['custom_delivery_date']) {
		wc_add_notice(__('Please select a delivery date.'), 'error');
	}
}

// Сохраняем дату доставки в мета-данные заказа
add_action('woocommerce_checkout_update_order_meta', 'wp_custom_delivery_date_update_order_meta');

function wp_custom_delivery_date_update_order_meta($order_id) {
	if ($_POST['custom_delivery_date']) {
		update_post_meta($order_id, 'custom_delivery_date', sanitize_text_field($_POST['custom_delivery_date']));
	}
}

// Отображаем дату доставки в админке
add_action('woocommerce_admin_order_data_after_billing_address', 'wp_custom_delivery_date_display_admin_order_meta', 10, 1);

function wp_custom_delivery_date_display_admin_order_meta($order) {
	echo '<p><strong>' . __('Delivery Date') . ':</strong> ' . get_post_meta($order->get_id(), 'custom_delivery_date', true) . '</p>';
}

// Скрипт для добавления календаря
add_action('wp_enqueue_scripts', 'wp_custom_delivery_date_enqueue');

function wp_custom_delivery_date_enqueue() {
	if (is_checkout()) {
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
		wp_add_inline_script('jquery-ui-datepicker', "
            jQuery(function($){
                $('#custom_delivery_date').datepicker({
                    minDate: +2,
                    maxDate: +14,
                    beforeShowDay: function(date) {
                        var productAvailableDays = wp_custom_get_available_delivery_days();
                        var day = date.getDay();
                        return productAvailableDays.includes(day);
                    }
                });
            });
        ");
	}
}

function wp_custom_get_available_delivery_days() {
	global $woocommerce;
	$available_days = [];

	foreach ($woocommerce->cart->get_cart() as $cart_item) {
		$product_id = $cart_item['product_id'];
		$product_delivery_days = get_post_meta($product_id, '_delivery_days', true);

		if ($product_delivery_days) {
			$available_days = explode(', ', $product_delivery_days);
		} else {
			$terms = wp_get_post_terms($product_id, 'product_cat');
			foreach ($terms as $term) {
				$term_delivery_days = get_term_meta($term->term_id, 'delivery_days', true);
				if ($term_delivery_days) {
					$available_days = explode(', ', $term_delivery_days);
					break;
				}
			}
		}
	}

	// Преобразуем текстовые дни в числовые (понедельник = 1, пятница = 5)
	$days_map = array(
		'Monday'    => 1,
		'Tuesday'   => 2,
		'Wednesday' => 3,
		'Thursday'  => 4,
		'Friday'    => 5,
		'Saturday'  => 6,
		'Sunday'    => 0
	);

	return array_map(function($day) use ($days_map) {
		return $days_map[$day];
	}, $available_days);
}
