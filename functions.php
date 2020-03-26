<?php
/* Reorder and rename Woocommerce account endpoints */
// Dismantle
// add_filter( 'woocommerce_account_menu_items' , 'customize_woocommerce_account_menu_items' );
function customize_woocommerce_account_menu_items() {
        $menuOrder= array(
            'dashboard'         =>  __('Dashboard', 'woocommerce'),
            'appointments'      =>  __('Appointments', 'woocommerce'),
            'packages'          =>  __('Packages', 'woocommerce'),
            'orders'            =>  __('Payment History', 'woocommerce'),
            'scheduled-orders'  =>  __('Scheduled Payments', 'woocommerce'),
            'edit-address'      =>  __('Billing Address', 'woocommerce'),
            'edit-account'      =>  __('Account Settings', 'woocommerce'),
            'payment-methods'   =>  __('Preferred Payment Method', 'woocommerce'),
            'customer-logout'   =>  __('Log Out', 'woocommerce')
        );
        return $menuOrder;
    }