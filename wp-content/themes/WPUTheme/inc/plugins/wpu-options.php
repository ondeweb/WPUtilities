<?php
include dirname( __FILE__ ) . '/../../z-protect.php';

/* ----------------------------------------------------------
   Options for the plugin "WPU Options"
   ------------------------------------------------------- */

add_filter( 'wpu_options_fields', 'set_wputh_options_fields', 10, 3 );
function set_wputh_options_fields( $options ) {
    $options['wpu_opt_phone'] = array(
        'label' => __( 'Phone' )
    );
    $options['wpu_opt_email'] = array(
        'label' => __( 'Email address' ),
        'type' => 'email',
        'test' => 'email'
    );
    $options['wpu_opt_address'] = array(
        'label' => __( 'Address' ),
        'type' => 'textarea'
    );
    return $options;
}
