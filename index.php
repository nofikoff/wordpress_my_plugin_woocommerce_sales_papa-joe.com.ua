<?php
/**
 * @package Novikov plugin
 * @version 1.0
 */
/*
Plugin Name: NOVIKOV управление заказами
Plugin URI: https://novikov.ua
Description: Плагин отображения скидок в корзине
Armstrong: My Plugin.
Author: Ruslan Novikov
Version: 9.9999
Author URI: https://novikov.ua
*/

/*
 * Сайт papa-joe.com.ua
Работаем с корзиной.

Есть категории: пицца, роллы, бургеры.
Должна быть акция в корзине - если пользователь берет 2 товара, то третий за 9 грн.
Условия:
2 бургера = бургер или пицца за 9 грн
2 пиццы = бургер или пицца за 9 грн

2 ролла = третий ролл за 9 грн

1.товар в подарок по цене должен стоить <= самого дешевого из двух товаров
например: я беру
пиццу за 179
бургер за 129
товар в подарок должен стоить дешевле, или быть равен сумме 129.(надеюсь нормально обьяснил)

2. если 3 товара стоят одинаково, то третий должен быть <= равен цене остальных
например:
пицца 100
бургер 100
третий товар можно выбрать за цену <= 100

Сценарии:
1.Если пользователь берет 3 товара, потом идет в корзину, то третий автоматом считает за 9 грн
2.Если пользователь не знает про акцию, берет только два товара, то ему на странице корзины выводится
баннер "У нас там акция, бла, бла, бла"(текст будет позже), после того как пользователь
возвращается и берет третий товар -> см. сценарий 1.

*************************
перебираем товары группируем по категориям (пиццыбургеры и роллы)
внутри категорий сортируем по цене
делим на 3
- деление без остатка, выставляем N смым дешевым товарам цену 9 грн
- если остаток 1 выводим сообщение докупите еще один бурген/пиццу по цене 9 грн

*/


function custom_wc_add_fee($cart)
{

    // БЫЛА ПРОБЛЕМА - переопределялся последний шаг
    // тут https://papa-joe.com.ua/checkout/

    //https://stackoverflow.com/questions/27023433/disable-ajax-on-checkout-for-woocommerce
    // обавил в футере
    // jQuery(document.body).on('update_checkout', function(e){
    //    e.stopImmediatePropagation();
    // });


    //блокируем левый какото аякс что мешает нашей корщине
    //    if ($_GET['wc-ajax'] <> '') {
    //        return;
    //    }
    /** И ЕСЛИ ТЫ ЭТО РАССКОМЕНТИРУЕШЬ - будет беда
     * заказы на последнем шаге будут без скидки
     * и в админке будут без скидки
     * что это за глюк с AJAX был без понятия
     * но вроде работает
     * и если опять AJAX упадет - это не вариант решения
     * 08/04/2020
     */


    global $position_sub_total;
    $free_item_price_discount = 9;
    $fee = 0;


    //перебираем товары группируем по категориям (пиццыбургеры и роллы)
    //внутри категорий сортируем по цене
    //делим на 3
    //- деление без остатка, выставляем N смым дешевым товарам цену 9 грн
    //- если остаток 1 выводим сообщение докупите еще один бурген/пиццу по цене 9 грн
    // Iterate through each cart item

    $regular_price_burgerpizza = [];
    $regular_price_rolls = [];

    foreach ($cart->get_cart() as $cart_item) {
        //ИТОГО с хитрыйм ID ключем ТЕКУЩЕЙ ПОЗИЦИИ [97d98119037c5b8a9663cb21fb8ebf47] => 240
        $position_sub_total[$cart_item['key']] = $cart_item['line_subtotal'];


        for ($i = 0; $i < $cart_item['quantity']; $i++) {

            //бургеры пица
            //бургеры пица
            //бургеры пица
            if (
                in_array(29, $cart_item['data']->get_category_ids()) //пицца
                OR in_array(30, $cart_item['data']->get_category_ids()) //бургеры
            ) {
                $regular_price_burgerpizza[] =
                    [
                        'price' => $cart_item['data']->get_regular_price(), // get sale price
                        'position_id' => $cart_item['key']
                    ];
            }

            //роллы
            //роллы
            //роллы
            //роллы
            if (in_array(35, $cart_item['data']->get_category_ids())) {
                $regular_price_rolls[] =
                    [
                        'price' => $cart_item['data']->get_regular_price(), // get sale price
                        'position_id' => $cart_item['key']
                    ];
            }


        }
    }
    if ($_SERVER["HTTP_CF_CONNECTING_IP"] == '46.219.57.140') {
        //echo "<pre>";
        //print_r($regular_price_burgerpizza);
    }

    /**  БУРГЕРОВ И ПИЦЦЫ ГРУППИРУЮТСЯ */
    /**  БУРГЕРОВ И ПИЦЦЫ ГРУППИРУЮТСЯ */
    /**  БУРГЕРОВ И ПИЦЦЫ ГРУППИРУЮТСЯ */
    // сортируем корзину по цене
    asort($regular_price_burgerpizza); // ключи сохранились старые
    $regular_price_burgerpizza = array_values($regular_price_burgerpizza); // рефреш ключей

    $number_free_items = floor(count($regular_price_burgerpizza) / 3);
    if ($number_free_items) {
        $message_free_item = "По акции 1+1 = 3 - <b>$number_free_items шт.</b> в Вашем заказе бургеры/пиццы идут по  $free_item_price_discount грн ";
        if (is_cart() || is_checkout() || is_order_received_page())
            wc_print_notice($message_free_item, 'success');
    }
    // попал на акцию- пойди выбери один бургер
    $one_item_must_add = count($regular_price_burgerpizza) % 3;
    if ($one_item_must_add == 2) {
        $message_onulus_item = "По акции 1+1 = 3 - Вам положен еще один  бургер или пицца за $free_item_price_discount грн. <b>Вернитесь в магазин и доберите 1 позицию</b>";
        if (is_cart() || is_checkout() || is_order_received_page())
            wc_print_notice($message_onulus_item, 'success');
    }
    // по возрастанию
    // рассчитаем скидку - N самых дешевых товаров в списке
    // рассчитаем скидку - N самых дешевых товаров в списке
    // рассчитаем скидку - N самых дешевых товаров в списке

    for ($i = 0; $i < $number_free_items; $i++) {
        $fee += $regular_price_burgerpizza[$i]['price'] - $free_item_price_discount;
        // накапливаем подытог цену для данной позиции (например когда несколько штук одной пзции)
        // ИТОГО с хитрыйм ID ключем типа [97d98119037c5b8a9663cb21fb8ebf47] => 240
        $position_sub_total[$regular_price_burgerpizza[$i]['position_id']] += $free_item_price_discount - $regular_price_burgerpizza[$i]['price'];
    }
    /** КОНЕЦ БЕРГЕРЫ ПИЦЦА */

    /** РОЛЛЫ ГРУППИРУЕМ НА СКИДКИ */
    /** РОЛЛЫ ГРУППИРУЕМ НА СКИДКИ */
    /** РОЛЛЫ ГРУППИРУЕМ НА СКИДКИ */
    // сортируем корзину по цене
    asort($regular_price_rolls); // ключи сохранились старые
    $regular_price_rolls = array_values($regular_price_rolls); // рефреш ключей

    $number_free_items = floor(count($regular_price_rolls) / 3);
    if ($number_free_items) {
        $message_free_item = "По акции 1+1 = 3 - <b>$number_free_items шт.</b> в Вашем заказе роллы идут по  $free_item_price_discount грн ";
        if (is_cart() || is_checkout() || is_order_received_page())
            wc_print_notice($message_free_item, 'success');
    }
    // попал на акцию- пойди выбери один бургер
    $one_item_must_add = count($regular_price_rolls) % 3;
    if ($one_item_must_add == 2) {
        $message_onulus_item = "По акции 1+1 = 3 - Вам положен еще один ролл за $free_item_price_discount грн. <b>Вернитесь в магазин и доберите 1 позицию</b>";
        if (is_cart() || is_checkout() || is_order_received_page())
            wc_print_notice($message_onulus_item, 'success');
    }
    // по возрастанию
    // рассчитаем скидку - N самых дешевых товаров в списке
    // рассчитаем скидку - N самых дешевых товаров в списке
    // рассчитаем скидку - N самых дешевых товаров в списке

    for ($i = 0; $i < $number_free_items; $i++) {
        $fee += $regular_price_rolls[$i]['price'] - $free_item_price_discount;
        // накапливаем подытог цену для данной позиции (например когда несколько штук одной пзции)
        // ИТОГО с хитрыйм ID ключем типа [97d98119037c5b8a9663cb21fb8ebf47] => 240
        $position_sub_total[$regular_price_rolls[$i]['position_id']] += $free_item_price_discount - $regular_price_rolls[$i]['price'];
    }
    /** КОНЕЦ РОЛЛЫ */


    WC()->cart->add_fee('Скидка "1+1=3"', -$fee);
}

add_action('woocommerce_cart_calculate_fees', 'custom_wc_add_fee');


// МЕНЯЕМ ЦЕНУ КАЖДОГО ЭЛЕМЕНТА ПО ЕГО ID
// МЕНЯЕМ ЦЕНУ КАЖДОГО ЭЛЕМЕНТА ПО ЕГО ID
// МЕНЯЕМ ЦЕНУ КАЖДОГО ЭЛЕМЕНТА ПО ЕГО ID
function bbloomer_change_cart_table_price_display($price, $values, $cart_item_key)
{

    global $position_sub_total;
    if (is_cart() || is_checkout() || is_order_received_page() || is_view_order_page() || is_checkout()) {

        // цена подытога изменилась из за хитрых скидок - выводим
        if (wc_price($position_sub_total[$cart_item_key]) != $price) {
            $price = "<span class='price_stripe'>" . $price . "</span>" . wc_price($position_sub_total[$cart_item_key]);
        }
    }
    return $price;
}

//add_filter( 'woocommerce_cart_item_price', 'bbloomer_change_cart_table_price_display', 30, 3 );
add_filter('woocommerce_cart_item_subtotal', 'bbloomer_change_cart_table_price_display', 30, 3);


//function add_custom_price($cart)
//{
//
//    // This is necessary for WC 3.0+
//    if (defined('DOING_AJAX')) return;
//
//    // Avoiding hook repetition (when using price calculations for example)
//    if (did_action('woocommerce_before_calculate_totals') >= 2)
//        return;
//
//    // Loop through cart items
//    foreach ($cart->get_cart() as $item) {
//        $item['data']->set_price(40);
//    }
//}
//add_action('woocommerce_before_calculate_totals', 'add_custom_price', 20, 1);


/////////////////////////////////////////////////////////////////////////////////
//  МЕНЯЕМ грн значек на слово грн
//  МЕНЯЕМ грн значек на слово грн
add_filter('woocommerce_currency_symbol', 'change_existing_currency_symbol', 10, 2);
function change_existing_currency_symbol($currency_symbol, $currency)
{
    return ($currency == 'UAH') ? ' грн' : $currency_symbol;
}


//function wc_minimum_order_amount() {
//    $price_min_order=100;
//    $minimum   = $price_min_order;
//    $flag_acia = 0;
//    if ( WC()->cart->total > 0 AND WC()->cart->total < $minimum AND ! $flag_acia ) {
//        //if (is_cart()) {
//        wc_print_notice(
//            sprintf( '<b>Текущая сумма вашего заказа : %s</b> ОПТ от %s грн. Заказы меньше %s грн. не принимается. Удачных и приятных Вам покупок!',
//                wc_price( WC()->cart->total ),
//                wc_price( $minimum ),
//                wc_price( $minimum )
//            ), 'error'
//        );
//    }
//}
//
//function disable_checkout_button() {
//    // Set this variable to specify a minimum order value
//    $price_min_order=100;
//    $minimum   = $price_min_order;
//    //$total = WC()->cart->get_cart_subtotal();
//    $total = WC()->cart->cart_contents_total;
//    if ( $total < $minimum ) {
//        remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
//        //echo '<a style="pointer-events: none !important;" href="#" class="checkout-button button alt wc-forward">Proceed to checkout</a>';
//    }
//}
//add_action( 'woocommerce_checkout_process', 'wc_minimum_order_amount' );
//add_action( 'woocommerce_before_cart', 'wc_minimum_order_amount' );
//add_action( 'woocommerce_proceed_to_checkout', 'disable_checkout_button', 1 );


//function add_custom_price( $cart_object ) {
//    $custom_price = 10; // This will be your custome price
//    foreach ( $cart_object->cart_contents as $key => $value ) {
//        //$value['data']->price = $custom_price;
//        // for WooCommerce version 3+ use:
//        print_r($value['data']);
//
//        //print_r($value['data']->get_category_ids());
//
//        //  новая цена
//        //$value['data']->set_price($custom_price);
//
//
//    }
//}
//add_action( 'woocommerce_before_calculate_totals', 'add_custom_price' );
//
//
//
//
//
//
//function mp_create_coupon( $data, $code ) {
//    // Check if the coupon has already been created in the database
//    global $wpdb;
//    $sql = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 1;", $code );
//    $coupon_id = $wpdb->get_var( $sql );
//    if ( empty( $coupon_id ) ) {
//        // Create a coupon with the properties you need
//        $data = array(
//            'discount_type'              => 'fixed_cart',
//            'coupon_amount'              => 100, // value
//            'individual_use'             => 'no',
//            'product_ids'                => array(),
//            'exclude_product_ids'        => array(),
//            'usage_limit'                => '',
//            'usage_limit_per_user'       => '1',
//            'limit_usage_to_x_items'     => '',
//            'usage_count'                => '',
//            'expiry_date'                => '2020-09-01', // YYYY-MM-DD
//            'free_shipping'              => 'no',
//            'product_categories'         => array(),
//            'exclude_product_categories' => array(),
//            'exclude_sale_items'         => 'no',
//            'minimum_amount'             => '',
//            'maximum_amount'             => '',
//            'customer_email'             => array()
//        );
//        // Save the coupon in the database
//        $coupon = array(
//            'post_title' => $code,
//            'post_content' => '',
//            'post_status' => 'publish',
//            'post_author' => 1,
//            'post_type' => 'shop_coupon'
//        );
//        $new_coupon_id = wp_insert_post( $coupon );
//        // Write the $data values into postmeta table
//        foreach ($data as $key => $value) {
//            update_post_meta( $new_coupon_id, $key, $value );
//        }
//    }
//    return $data;
//}
//add_filter ( 'woocommerce_get_shop_coupon_data', 'mp_create_coupon', 10, 2  );
//
//
//
//
//
//
//
//
// Generating dynamically the product "sale price"
//function custom_dynamic_sale_price( $sale_price, $product ) {
//    $rate = 0.1;
//    if( empty($sale_price) || $sale_price == 0 )
//        return $product->get_regular_price() * $rate;
//    else
//        return $sale_price;
//};
//add_filter( 'woocommerce_product_get_sale_price', 'custom_dynamic_sale_price', 10, 2 );
//add_filter( 'woocommerce_product_variation_get_sale_price', 'custom_dynamic_sale_price', 10, 2 );
//
//
//
//
//
//
//function set_cart_item_sale_price( $cart ) {
//    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
//        return;
//
//    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
//        return;
//
//    // Iterate through each cart item
//    foreach( $cart->get_cart() as $cart_item ) {
//        $price = $cart_item['data']->get_regular_price() * 0.5; // get sale price
//        $cart_item['data']->set_price( $price ); // Set the sale price
//
//    }
//}
//add_action( 'woocommerce_before_calculate_totals', 'set_cart_item_sale_price', 20, 1 );
//
//
//
//
//
//
//
//
// Hook before calculate fees
///**
// * Add custom fee if more than three article
// * @param WC_Cart $cart
// */
//function add_user_discounts( WC_Cart $cart ){
//    //any of your rules
//    // Calculate the amount to reduce
//    $discount = $cart->get_subtotal() * 0.5;
//
//    $cart->add_fee( 'Test discount 50%', -$discount);
//}
//add_action('woocommerce_cart_calculate_fees' , 'add_user_discounts');
//
//
//
//
//
