<?php
/*
Plugin Name: WebRainbow - Delivery Date
Description: This plugin adds a custom delivery date field to the WooCommerce checkout page.
Version: 1.0
Author: WebRainbow
*/

// Подключение Bootstrap 5 и стилей для админки и фронта
function webrainbow_delivery_date_enqueue_scripts()
{
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.0.0', true);
    wp_enqueue_style('webrainbow-delivery-date', plugins_url('css/webrainbow-delivery-date.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'webrainbow_delivery_date_enqueue_scripts');

function webrainbow_delivery_date_admin_enqueue_scripts()
{
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.0.0', true);
    wp_enqueue_style('webrainbow-delivery-date-admin', plugins_url('css/webrainbow-delivery-date-admin.css', __FILE__));
}

add_action('admin_enqueue_scripts', 'webrainbow_delivery_date_admin_enqueue_scripts');

// Регистрация настроек плагина
function webrainbow_delivery_date_settings_init()
{
    register_setting('webrainbow_delivery_date_options', 'webrainbow_delivery_date_settings');

    add_settings_section(
        'webrainbow_delivery_date_section',
        __('Настройки даты доставки', 'webrainbow-delivery-date'),
        'webrainbow_delivery_date_section_callback',
        'webrainbow_delivery_date_options'
    );

    add_settings_field(
        'webrainbow_delivery_date_default_days',
        __('Дни доставки по умолчанию', 'webrainbow-delivery-date'),
        'webrainbow_delivery_date_default_days_callback',
        'webrainbow_delivery_date_options',
        'webrainbow_delivery_date_section'
    );

    add_settings_field(
        'webrainbow_delivery_date_category_days',
        __('Дни доставки по категориям', 'webrainbow-delivery-date'),
        'webrainbow_delivery_date_category_days_callback',
        'webrainbow_delivery_date_options',
        'webrainbow_delivery_date_section'
    );

    add_settings_field(
        'webrainbow_delivery_date_min_days',
        __('Минимальное количество дней до доставки', 'webrainbow-delivery-date'),
        'webrainbow_delivery_date_min_days_callback',
        'webrainbow_delivery_date_options',
        'webrainbow_delivery_date_section'
    );

    add_settings_field(
        'webrainbow_delivery_date_category_min_days',
        __('Исключения для категорий товаров', 'webrainbow-delivery-date'),
        'webrainbow_delivery_date_category_min_days_callback',
        'webrainbow_delivery_date_options',
        'webrainbow_delivery_date_section'
    );
}

add_action('admin_init', 'webrainbow_delivery_date_settings_init');

// Добавление страницы настроек в меню администратора
function webrainbow_delivery_date_options_page()
{
    add_options_page(
        __('Настройки даты доставки', 'webrainbow-delivery-date'),
        __('Дата доставки', 'webrainbow-delivery-date'),
        'manage_options',
        'webrainbow_delivery_date_options',
        'webrainbow_delivery_date_options_page_html'
    );
}

add_action('admin_menu', 'webrainbow_delivery_date_options_page');


// Вывод поля для настройки минимального количества дней до доставки
function webrainbow_delivery_date_min_days_callback()
{
    $options = get_option('webrainbow_delivery_date_settings');
    $min_days = isset($options['min_days']) ? $options['min_days'] : 0;
    ?>
    <div class="form-group">
        <input type="number" class="form-control" id="webrainbow_delivery_date_min_days"
               name="webrainbow_delivery_date_settings[min_days]" value="<?php echo $min_days; ?>" min="0">
        <small class="form-text text-muted"><?php _e('Укажите минимальное количество дней от текущего момента, через которое будет доступна ближайшая дата доставки.', 'webrainbow-delivery-date'); ?></small>
    </div>
    <?php
}

// Вывод полей для настройки исключений для категорий товаров
function webrainbow_delivery_date_category_min_days_callback()
{
    $options = get_option('webrainbow_delivery_date_settings');
    $categories = get_terms('product_cat', array('hide_empty' => false));
    ?>
    <div class="form-group">
        <div id="webrainbow-delivery-date-category-min-days-container">
            <?php
            if (isset($options['category_min_days']) && is_array($options['category_min_days'])) {
                foreach ($options['category_min_days'] as $index => $category_data) {
                    $category_id = $category_data['category'];
                    $category_min_days = $category_data['min_days'];
                    ?>
                    <div class="webrainbow-delivery-date-category-min-days-row">
                        <select name="webrainbow_delivery_date_settings[category_min_days][<?php echo $index; ?>][category]">
                            <option value=""><?php _e('Выберите категорию', 'webrainbow-delivery-date'); ?></option>
                            <?php foreach ($categories as $category) { ?>
                                <option value="<?php echo $category->term_id; ?>" <?php selected($category_id, $category->term_id); ?>><?php echo $category->name; ?></option>
                            <?php } ?>
                        </select>
                        <input type="number"
                               name="webrainbow_delivery_date_settings[category_min_days][<?php echo $index; ?>][min_days]"
                               value="<?php echo $category_min_days; ?>" min="0">
                        <button type="button"
                                class="button btn btn-warning webrainbow-delivery-date-remove-category-min-days"><?php _e('Удалить', 'webrainbow-delivery-date'); ?></button>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <button type="button"
                class="button btn btn-submit webrainbow-delivery-date-add-category-min-days"><?php _e('Добавить', 'webrainbow-delivery-date'); ?></button>
        <p class="description"><?php _e('Укажите исключения для категорий товаров, чтобы определенные категории можно было заказывать только через определенное количество дней.', 'webrainbow-delivery-date'); ?></p>
    </div>
    <script>
        jQuery(function ($) {
            var categoryMinDaysContainer = $('#webrainbow-delivery-date-category-min-days-container');
            var addCategoryMinDaysButton = $('.webrainbow-delivery-date-add-category-min-days');
            var removeCategoryMinDaysButton = '.webrainbow-delivery-date-remove-category-min-days';

            var newRowIndex = <?php echo isset($options['category_min_days']) ? count($options['category_min_days']) : 0; ?>;

            addCategoryMinDaysButton.on('click', function () {
                var row = $('<div class="webrainbow-delivery-date-category-min-days-row">' +
                    '<select name="webrainbow_delivery_date_settings[category_min_days][' + newRowIndex + '][category]">' +
                    '<option value=""><?php _e('Выберите категорию', 'webrainbow-delivery-date'); ?></option>' +
                    <?php foreach ($categories as $category) { ?>
                    '<option value="<?php echo $category->term_id; ?>"><?php echo $category->name; ?></option>' +
                    <?php } ?>
                    '</select>' +
                    '<input type="number" name="webrainbow_delivery_date_settings[category_min_days][' + newRowIndex + '][min_days]" value="0" min="0">' +
                    '<button type="button" class="button webrainbow-delivery-date-remove-category-min-days"><?php _e('Удалить', 'webrainbow-delivery-date'); ?></button>' +
                    '</div>');
                categoryMinDaysContainer.append(row);
                newRowIndex++;
            });

            categoryMinDaysContainer.on('click', removeCategoryMinDaysButton, function () {
                $(this).closest('.webrainbow-delivery-date-category-min-days-row').remove();
            });
        });
    </script>
    <?php
}

// Вывод HTML для страницы настроек
function webrainbow_delivery_date_options_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('webrainbow_delivery_date_options');
            do_settings_sections('webrainbow_delivery_date_options');
            submit_button(__('Сохранить изменения', 'webrainbow-delivery-date'));
            ?>
        </form>
    </div>
    <?php
}

// Вывод описания секции настроек
function webrainbow_delivery_date_section_callback()
{
    echo __('Настройте опции для поля даты доставки на странице оформления заказа.', 'webrainbow-delivery-date');
}

// Вывод поля для настройки дней доставки по умолчанию
function webrainbow_delivery_date_default_days_callback()
{
    $options = get_option('webrainbow_delivery_date_settings');
    $days = array(
        'monday' => __('Понедельник', 'webrainbow-delivery-date'),
        'tuesday' => __('Вторник', 'webrainbow-delivery-date'),
        'wednesday' => __('Среда', 'webrainbow-delivery-date'),
        'thursday' => __('Четверг', 'webrainbow-delivery-date'),
        'friday' => __('Пятница', 'webrainbow-delivery-date'),
        'saturday' => __('Суббота', 'webrainbow-delivery-date'),
        'sunday' => __('Воскресенье', 'webrainbow-delivery-date'),
    );
    ?>
    <select id="webrainbow_delivery_date_default_days" name="webrainbow_delivery_date_settings[default_days][]"
            multiple>
        <?php foreach ($days as $day_key => $day_name) { ?>
            <option value="<?php echo $day_key; ?>" <?php echo isset($options['default_days']) && in_array($day_key, $options['default_days']) ? 'selected' : ''; ?>><?php echo $day_name; ?></option>
        <?php } ?>
    </select>
    <p class="description"><?php _e('Выберите дни доставки по умолчанию для всех категорий товаров.', 'webrainbow-delivery-date'); ?></p>
    <?php
}

// Вывод поля для настройки дней доставки по категориям
// Вывод поля для настройки дней доставки по категориям
function webrainbow_delivery_date_category_days_callback()
{
    $options = get_option('webrainbow_delivery_date_settings');
    $categories = get_terms('product_cat', array('hide_empty' => false));
    $days = array(
        'monday' => __('Понедельник', 'webrainbow-delivery-date'),
        'tuesday' => __('Вторник', 'webrainbow-delivery-date'),
        'wednesday' => __('Среда', 'webrainbow-delivery-date'),
        'thursday' => __('Четверг', 'webrainbow-delivery-date'),
        'friday' => __('Пятница', 'webrainbow-delivery-date'),
        'saturday' => __('Суббота', 'webrainbow-delivery-date'),
        'sunday' => __('Воскресенье', 'webrainbow-delivery-date'),
    );
    ?>
    <div id="webrainbow-delivery-date-category-days-container">
        <?php
        if (isset($options['category_days']) && is_array($options['category_days'])) {
            foreach ($options['category_days'] as $index => $category_data) {
                $category_id = $category_data['category'];
                $category_days = $category_data['days'];
                ?>
                <div class="webrainbow-delivery-date-category-days-row form-group">
                    <select name="webrainbow_delivery_date_settings[category_days][<?php echo $index; ?>][category]">
                        <option value=""><?php _e('Выберите категорию', 'webrainbow-delivery-date'); ?></option>
                        <?php foreach ($categories as $category) { ?>
                            <option value="<?php echo $category->term_id; ?>" <?php selected($category_id, $category->term_id); ?>><?php echo $category->name; ?></option>
                        <?php } ?>
                    </select>
                    <select name="webrainbow_delivery_date_settings[category_days][<?php echo $index; ?>][days][]"
                            multiple>
                        <?php foreach ($days as $day_key => $day_name) { ?>
                            <option value="<?php echo $day_key; ?>" <?php echo isset($category_days) && in_array($day_key, $category_days) ? 'selected' : ''; ?>><?php echo $day_name; ?></option>
                        <?php } ?>
                    </select>
                    <button type="button"
                            class="button btn btn-warning webrainbow-delivery-date-remove-category-days"><?php _e('Удалить', 'webrainbow-delivery-date'); ?></button>
                </div>
                <?php
            }
        }
        ?>
    </div>
    <button type="button"
            class="button webrainbow-delivery-date-add-category-days"><?php _e('Добавить', 'webrainbow-delivery-date'); ?></button>
    <p class="description"><?php _e('Выберите категории и дни доставки для каждой из них. Если товар в корзине принадлежит одной из выбранных категорий, весь заказ будет доставлен в соответствии с днями доставки этой категории.', 'webrainbow-delivery-date'); ?></p>
    <script>
        jQuery(function ($) {
            var categoryDaysContainer = $('#webrainbow-delivery-date-category-days-container');
            var addCategoryDaysButton = $('.webrainbow-delivery-date-add-category-days');
            var removeCategoryDaysButton = '.webrainbow-delivery-date-remove-category-days';

            var newRowIndex = <?php echo isset($options['category_days']) ? count($options['category_days']) : 0; ?>;

            addCategoryDaysButton.on('click', function () {
                var row = $('<div  class="webrainbow-delivery-date-category-days-row input-group mb-3">' +
                    '<select class="form-select" name="webrainbow_delivery_date_settings[category_days][' + newRowIndex + '][category]">' +
                    '<option value=""><?php _e('Выберите категорию', 'webrainbow-delivery-date'); ?></option>' +
                    <?php foreach ($categories as $category) { ?>
                    '<option value="<?php echo $category->term_id; ?>"><?php echo $category->name; ?></option>' +
                    <?php } ?>
                    '</select>' +
                    '<select name="webrainbow_delivery_date_settings[category_days][' + newRowIndex + '][days][]" multiple>' +
                    <?php foreach ($days as $day_key => $day_name) { ?>
                    '<option value="<?php echo $day_key; ?>"><?php echo $day_name; ?></option>' +
                    <?php } ?>
                    '</select>' +
                    '<button type="button"  class="button btn btn-warning webrainbow-delivery-date-remove-category-days"><?php _e('Удалить', 'webrainbow-delivery-date'); ?></button>' +
                    '</div>');
                categoryDaysContainer.append(row);
                newRowIndex++;
            });

            categoryDaysContainer.on('click', removeCategoryDaysButton, function () {
                $(this).closest('.webrainbow-delivery-date-category-days-row').remove();
            });
        });
    </script>
    <?php
}

// Зарегистрировать скрипты и стили
function delivery_date_scripts()
{
    wp_enqueue_style('jquery-ui-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css');
    wp_enqueue_script('jquery-ui-datepicker');
}

add_action('wp_enqueue_scripts', 'delivery_date_scripts');


function delivery_date_field($checkout)
{
    echo '<div id="delivery_date_field"><label for="delivery_date" style="font-weight: bold; color: red;">' . __('Выберите дату доставки', 'woocommerce') . ' <abbr class="required" title="обязательно">*</abbr></label> <br>
          <input type="text" name="delivery_date" id="delivery_date" placeholder="' . __('Выберите дату', 'woocommerce') . '" readonly="readonly" /></div>';
}
add_action('woocommerce_after_checkout_billing_form', 'delivery_date_field');
// Проверка наличия значения в поле выбора даты доставки
function validate_delivery_date()
{
    if (empty($_POST['delivery_date'])) {
        wc_add_notice(__('Пожалуйста, выберите дату доставки.', 'woocommerce'), 'error');
    }
}
add_action('woocommerce_checkout_process', 'validate_delivery_date');
// Инициализировать календарь



function delivery_date_scripts_footer()
{
    date_default_timezone_set('Asia/Jerusalem'); // Установим часовой пояс для Израиля

    $options = get_option('webrainbow_delivery_date_settings');
    $default_days = isset($options['default_days']) ? $options['default_days'] : array();
    $category_days = isset($options['category_days']) ? $options['category_days'] : array();
    $min_days = isset($options['min_days']) ? intval($options['min_days']) : 2; // Общее правило - минимум 2 дня
    $category_min_days = isset($options['category_min_days']) ? $options['category_min_days'] : array();
    $cart_items = WC()->cart->get_cart();
    $selected_days = $default_days;

    $is_category_235_in_cart = false;
    $is_category_271_in_cart = false;

    foreach ($cart_items as $cart_item) {
        $product_id = $cart_item['product_id'];
        $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        if (in_array(235, $product_categories)) {
            $is_category_235_in_cart = true;
            $selected_days = array('tuesday', 'saturday');
            break;
        }
        // Проверка для категории 271
        if (in_array(271, $product_categories)) {
            $is_category_271_in_cart = true;
            $selected_days = array('tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
            break;
        }
    }

    $category_min_days_array = array();
    foreach ($category_min_days as $category_data) {
        $category_min_days_array[$category_data['category']] = intval($category_data['min_days']);
    }
    ?>
    <script>
        jQuery(function ($) {
            var selectedDays = <?php echo json_encode($selected_days); ?>;
            var minDate = <?php echo $min_days; ?>;
            var categoryMinDays = <?php echo json_encode($category_min_days_array); ?>;
            var isCategory235InCart = <?php echo json_encode($is_category_235_in_cart); ?>;
            var isCategory271InCart = <?php echo json_encode($is_category_271_in_cart); ?>;
            var currentDay = new Date().getDay();
            var currentHour = new Date().getHours();

            function getCategoryMinDays() {
                var minDays = minDate;
                if (isCategory235InCart) {
                    if (currentDay === 1 && currentHour < 20) { // Понедельник до 8 вечера
                        minDays = 1; // Доставка на вторник этой же недели
                    } else if (currentDay === 5 && currentHour < 20) { // Пятница до 8 вечера
                        minDays = 1; // Доставка на субботу этой же недели
                    } else {
                        // Установим стандартное значение в 2 дня
                        minDays = minDate;
                    }
                } else if (isCategory271InCart) {
                    minDays = minDate; // Для категории 271 стандартное минимальное количество дней
                } else {
                    <?php foreach ($cart_items as $cart_item) { ?>
                    var productId = <?php echo $cart_item['product_id']; ?>;
                    var productCategories = <?php echo json_encode(wp_get_post_terms($cart_item['product_id'], 'product_cat', array('fields' => 'ids'))); ?>;
                    productCategories.forEach(function (categoryId) {
                        if (categoryMinDays.hasOwnProperty(categoryId) && categoryMinDays[categoryId] > minDays) {
                            minDays = categoryMinDays[categoryId];
                        }
                    });
                    <?php } ?>
                }
                return minDays;
            }

            function isAvailable(date) {
                var dayOfWeek = date.getDay();
                var dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                var dayName = dayNames[dayOfWeek];

                // Добавляем проверку на исключение дат с 1/10/2024 по 3/10/2024 включительно
                var unavailableStartDate = new Date('2024-10-01T00:00:00');
                var unavailableEndDate = new Date('2024-10-03T23:59:59');

                // Сбрасываем время у проверяемой даты для корректного сравнения
                var checkDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());

                if (checkDate >= unavailableStartDate && checkDate <= unavailableEndDate) {
                    return [false, ""];
                }
                // Исключаем воскресенье и понедельник
                if (dayName === 'sunday' || dayName === 'monday') {
                    return [false, ""];
                }
                var isDaySelected = $.inArray(dayName, selectedDays) !== -1;
                return [isDaySelected, ""];
            }

            $("#delivery_date").datepicker({
                dateFormat: "yy-mm-dd",
                minDate: getCategoryMinDays(),
                beforeShowDay: function (date) {
                    return isAvailable(date);
                }
            });
        });
    </script>
    <?php
}


add_action('wp_footer', 'delivery_date_scripts_footer');


function save_delivery_date($order_id)
{
    if (isset($_POST['delivery_date'])) {
        $delivery_date = sanitize_text_field($_POST['delivery_date']);
        // Получение объекта заказа
        $order = wc_get_order($order_id);

        if ($order) {
            $order->update_meta_data('_delivery_date', $delivery_date);
            $order->save();
           // $saved_delivery_date = $order->get_meta('_delivery_date');
        } else {
            error_log('Не удалось получить объект заказа.');
        }
    } else {
        error_log('Поле даты доставки не найдено в POST данных.');
    }
}

add_action('woocommerce_checkout_update_order_meta', 'save_delivery_date', 10, 1);

function display_delivery_date_in_email($order, $sent_to_admin, $plain_text, $email)
{
    $delivery_date = $order->get_meta('_delivery_date');

    if ($delivery_date) {
        $formatted_date = date('d-m-Y', strtotime($delivery_date));
        echo '<p style="color: red; font-size: 1.5rem"><strong>' . __('Дата доставки', 'woocommerce') . ':</strong> ' . esc_html($formatted_date) . '</p>';
    } else {
        error_log('Дата доставки не найдена в мета-полях заказа.');
    }
}


add_action('woocommerce_email_order_details', 'display_delivery_date_in_email', 10, 4);

