<?php


class WC_Gateway_Online extends WC_Payment_Gateway
{
    protected string $instructions;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id                 = 'online_gateway';
        $this->icon               = apply_filters('woocommerce_online_icon', '');
        $this->has_fields         = false;
        $this->method_title       = __('Online payment', 'wc-gateway-online');
        $this->method_description = __('Online payment without any API action.', 'wc-gateway-online');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_thankyou_' . $this->id, [$this, 'thankyou_page']);

        // Customer Emails
        add_action('woocommerce_email_before_order_table', [$this, 'email_instructions'], 10, 3);
    }


    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = apply_filters('wc_online_form_fields', [

            'enabled' => [
                'title'   => __('Enable/Disable', 'wc-gateway-online'),
                'type'    => 'checkbox',
                'label'   => __('Enable Online Payment', 'wc-gateway-online'),
                'default' => 'yes'
            ],

            'title' => [
                'title'       => __('Title', 'wc-gateway-online'),
                'type'        => 'text',
                'description' => __('This controls the title.', 'wc-gateway-online'),
                'default'     => __('Online Payment', 'wc-gateway-online'),
                'desc_tip'    => true,
            ],

            'description' => [
                'title'       => __('Description', 'wc-gateway-online'),
                'type'        => 'textarea',
                'description' => __(
                    'Payment method description that the customer will see on your checkout.',
                    'wc-gateway-online'
                ),
                'default'     => __('Please send me some money.', 'wc-gateway-online'),
                'desc_tip'    => true,
            ],

            'instructions' => [
                'title'       => __('Instructions', 'wc-gateway-online'),
                'type'        => 'textarea',
                'description' => __(
                    'Instructions that will be added to the thank you page and emails.',
                    'wc-gateway-online'
                ),
                'default'     => '',
                'desc_tip'    => true,
            ],
        ]);
    }


    /**
     * Output for the order received page.
     */
    public function thankyou_page()
    {
        if (isset($this->instructions)) {
            echo wpautop(wptexturize($this->instructions));
        }
    }


    /**
     * Add content to the WC emails.
     *
     * @access public
     *
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false)
    {
//        if (isset($this->instructions) && ! $sent_to_admin && $this->id === $order->get_payment_method()
//            && $order->has_status('on-hold'))
//        {
//           echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
//        }
    }


    /**
     * Process the payment and return the result
     */
    public function process_payment($order_id):array
    {
        $order = wc_get_order($order_id);

        if ($order->get_status() === 'pending') {
            // Get processed data.
            $order   = wc_get_order($order_id);
            $bill_id = wp_rand(0, 99999999999999999);
            $order->set_transaction_id($bill_id);

            // Reduce stock levels.
            wc_reduce_stock_levels($order->get_id());

            // Remove cart.
            wc_empty_cart();

            $order->add_order_note(__('The client started paying.', 'plugin'));
            // .... payment process
            $order->add_order_note(__('The client completed the payment.', 'plugin'));
            $order->update_status('complete', __('The order was successfully paid online', 'plugin'));
            $order->payment_complete($bill_id);
        } elseif ( ! $order->is_paid() && $order->get_status() === 'cancelled') {
            error_log('fail');
        } else {
            error_log('..');
        }

        // Return thankyou redirect
        return [
            'result'   => 'success',
            'success'  => $this->get_return_url($order),
            'redirect' => $this->get_return_url($order)
        ];
    }

}

