<?php
/**
 * Plugin Name: WooCommerce Payment Online Gateway Emulator
 * Description: WooCommerce Payment Online Gateway Emulator
 * Author: Aleksei Tikhomirov
 *
 * Version: 1.0.3
 *
 * Requires at least: 4.6
 * Tested up to: 6.7.2
 *
 * Requires PHP: 7.4
 * Recommend PHP: 8.3
 *
 * WC requires at least: 3.4.0
 * WC tested up to: 8.6.2
 *
 * Text Domain: wc-online-payment-emulator
 * license: GPL3.0+
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined('ABSPATH') or exit;


// Make sure WooCommerce is active
add_action('plugins_loaded', function () {

    if ( ! function_exists('WC')) {
        add_action('admin_notices', 'wc_online_notice');
        return;
    }

    add_action('before_woocommerce_init', function () {
        if (class_exists(FeaturesUtil::class)) {
            FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                __FILE__,
                true // true (compatible, default) or false (not compatible)
            );
        }
    });

    add_filter('woocommerce_payment_gateways', fn($gateways) => array_merge($gateways, ['online' => 'WC_Gateway_Online']));
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_online_gateway_plugin_links', 10, 1);

    require_once 'WC_Gateway_Online.php';
});


function wc_online_gateway_plugin_links($links): array
{
    $link = admin_url('admin.php?page=wc-settings&tab=checkout&section=online_gateway');
    $text = __('Configure','wc-online-payment-emulator');
    $plugin_links = ['<a href="' . $link . '">' . $text . '</a>'];

    return array_merge($plugin_links, $links);
}

function wc_online_notice(){
    $msg = __('Woocommerce is not activated. To work "WooCommerce Payment Online Gateway Emulator" plugin, 
                you need to install and activate WooCommerce','wc-online-payment-emulator');
    echo '<div class="notice notice-error is-dismissible"><p>'. $msg . '</p></div>';
}