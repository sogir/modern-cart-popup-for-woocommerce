/**
 * WooCommerce Custom Cart Actions (All-in-One: V3.1)
 * 
 * 1. Single Product: [single_product_bundle] 
 *    - Logic: Custom Form + Nice Qty + JS Redirect for Buy Now
 * 
 * 2. Loop Grid: [loop_outline_atc] and [loop_buy_now]
 *    - Logic: Simple Links
 */

/* =========================================
   PART 1: SINGLE PRODUCT BUNDLE SHORTCODE
   ========================================= */
add_shortcode( 'single_product_bundle', 'wpcode_single_product_logic' );

function wpcode_single_product_logic() {
    global $product;
    if ( ! $product ) { $product = wc_get_product( get_the_ID() ); }
    if ( ! $product ) return '';

    ob_start();

    // 1. Variable/External/Grouped Fallback (Use standard template for complex types)
    if ( ! $product->is_type( 'simple' ) ) {
        woocommerce_template_single_add_to_cart();
        return ob_get_clean();
    }

    // 2. Simple Product Logic
    $action_url = esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) );
    
    // Quantity Settings
    $min_qty = $product->get_min_purchase_quantity();
    $max_qty = $product->get_max_purchase_quantity();
    $step    = apply_filters( 'woocommerce_quantity_input_step', 1, $product ); // Default to 1
    
    // Max Attribute logic
    $max_attr = ( $max_qty > 0 ) ? 'max="' . esc_attr( $max_qty ) . '"' : ''; 
    
    // Checkout URL for JS
    $checkout_url = wc_get_checkout_url();
    ?>

    <!-- Form Wrapper -->
    <form class="cart wpcode-sp-form" action="<?php echo $action_url; ?>" method="post" enctype='multipart/form-data'>
        
        <div class="wpcode-sp-row">
            <!-- Quantity Input -->
            <?php if ( ! $product->is_sold_individually() ) : ?>
                <div class="wpcode-nice-qty">
                    <button type="button" class="qty-control minus">-</button>
                    <input type="number" 
                           id="quantity_<?php echo $product->get_id(); ?>"
                           class="input-text qty text" 
                           step="<?php echo esc_attr( $step ); ?>" 
                           min="<?php echo esc_attr( $min_qty ); ?>" 
                           <?php echo $max_attr; ?>
                           name="quantity" 
                           value="<?php echo esc_attr( $min_qty ); ?>" 
                           title="Qty" 
                           size="4" 
                           inputmode="numeric" />
                    <button type="button" class="qty-control plus">+</button>
                </div>
            <?php endif; ?>

            <!-- Outline Add To Cart (Standard Submit) -->
            <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="button alt wpcode-sp-outline-atc">
                <?php echo esc_html( $product->single_add_to_cart_text() ); ?>
            </button>
        </div>

        <!-- Buy Now Button (JS Redirect) -->
        <button type="button" 
                class="wpcode-sp-buy-now-trigger" 
                data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
                data-checkout-url="<?php echo esc_url( $checkout_url ); ?>">
            <?php echo esc_html__( 'Buy Now', 'woocommerce' ); ?>
        </button>

    </form>
    <?php
    return ob_get_clean();
}

/* =========================================
   PART 2: LOOP / GRID SHORTCODES
   ========================================= */

// A. Loop Outline ATC
add_shortcode( 'loop_outline_atc', 'wpcode_loop_atc_logic' );
function wpcode_loop_atc_logic() {
    global $product;
    if ( ! $product ) { $product = wc_get_product( get_the_ID() ); }
    if ( ! $product ) return '';

    $class = implode( ' ', array_filter( array(
        'button',
        'product_type_' . $product->get_type(),
        $product->is_purchasable() && $product->is_in_stock() ? 'add_to_cart_button' : '',
        $product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() ? 'ajax_add_to_cart' : '',
        'wpcode-loop-outline-btn'
    ) ) );

    return sprintf( 
        '<a href="%s" data-quantity="1" class="%s" data-product_id="%s" aria-label="%s" rel="nofollow">%s</a>',
        esc_url( $product->add_to_cart_url() ),
        esc_attr( $class ),
        esc_attr( $product->get_id() ),
        esc_attr( $product->add_to_cart_description() ),
        esc_html( $product->add_to_cart_text() )
    );
}

// B. Loop Buy Now
add_shortcode( 'loop_buy_now', 'wpcode_loop_buynow_logic' );
function wpcode_loop_buynow_logic() {
    global $product;
    if ( ! $product ) { $product = wc_get_product( get_the_ID() ); }
    if ( ! $product ) return '';

    if ( $product->is_type( 'variable' ) ) {
        $link = get_permalink( $product->get_id() );
        $text = 'Select Options';
    } else {
        // Appends ?add-to-cart=ID&is_buy_now=1
        $link = add_query_arg( array( 'add-to-cart' => $product->get_id(), 'is_buy_now' => '1' ), wc_get_cart_url() );
        $text = 'Buy Now';
    }

    return '<a href="' . esc_url( $link ) . '" class="button wpcode-loop-buynow-btn">' . $text . '</a>';
}

/* =========================================
   PART 3: REDIRECT LOGIC (FOR LOOP ONLY)
   ========================================= */
add_filter( 'woocommerce_add_to_cart_redirect', 'wpcode_loop_redirect_logic', 99, 1 );

function wpcode_loop_redirect_logic( $url ) {
    // Only handles the Loop Link Click (Single product now handled via JS)
    if ( isset( $_REQUEST['is_buy_now'] ) && $_REQUEST['is_buy_now'] == '1' ) {
        return wc_get_checkout_url();
    }
    return $url;
}

/* =========================================
   PART 4: CSS & JS ASSETS
   ========================================= */
add_action('wp_head', 'wpcode_global_assets');
function wpcode_global_assets() {
    ?>
    <style>
        /* --- SINGLE PRODUCT STYLES --- */
        
        /* 1. Gap Reduction */
        .wpcode-sp-row { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 10px;
        }
        
        /* Nice Qty Box */
        .wpcode-nice-qty {
            display: flex; 
            border: 1px solid #000; 
            border-radius: 5px; 
            overflow: hidden;
            width: 100px; 
            flex-shrink: 0; 
            height: 42px; 
            position: relative;
        }
        
        /* Input Field */
        .wpcode-nice-qty input.qty {
            width: 100% !important; 
            border: none !important; 
            text-align: center;
            padding: 0 !important; 
            margin: 0 !important; 
            -moz-appearance: textfield;
            height: 100% !important; 
            background: transparent !important;
            color: #000 !important;
            font-weight: 500;
        }
        
        /* +/- Buttons (FIXED COLOR) */
        .qty-control {
            background: #fff !important; 
            border: none !important; 
            width: 30px; 
            font-size: 18px; 
            cursor: pointer;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100%; 
            color: #000 !important; /* Forces Black */
            text-decoration: none !important;
            padding: 0 !important;
            margin: 0 !important;
            line-height: 1 !important;
            z-index: 10;
        }
        .qty-control:hover { background: #f0f0f0 !important; color: #000 !important; }
        .qty-control:visited, .qty-control:focus { color: #000 !important; }

        /* Outline ATC */
        .wpcode-sp-outline-atc {
            flex-grow: 1; 
            background: transparent !important; 
            color: #000 !important;
            border: 1px solid #000 !important; 
            border-radius: 5px !important;
            text-align: center; 
            padding: 0 10px !important; 
            height: 42px !important;
            line-height: 42px !important; 
            position: relative; 
            transition: all 0.3s;
            font-weight: 400!important;
            font-size: 16px!important;
        }
        .wpcode-sp-outline-atc:hover { background: #000 !important; color: #fff !important; }

        /* 2. Fix Animation Wheel Position */
        .wpcode-sp-outline-atc.loading {
            opacity: 0.7;
        }
        .wpcode-sp-outline-atc.loading::after {
            font-family: WooCommerce;
            content: "\e01c";
            vertical-align: top;
            font-weight: 400;
            position: absolute;
            top: 50%;
/*             right: 10px; */
            animation: spin 3s linear infinite;
            line-height: 1;            
            font-size: 15px!important;
        }

        /* Buy Now */
        .wpcode-sp-buy-now-trigger {
            width: 100%; 
            background: #000 !important; 
            color: #fff !important;
            border: 1px solid #000 !important; 
            border-radius: 5px !important;
            padding: 10px 0 !important; 
            cursor: pointer; 
            font-weight: 400!important;
            font-size: 16px!important;
            display: block;
        }
        .wpcode-sp-buy-now-trigger:hover { opacity: 0.9; }

        /* --- LOOP STYLES --- */
        .wpcode-loop-outline-btn {
            display: block; width: 100%; text-align: center;
            background: transparent!important; color: #000!important;
            border: 1px solid #000!important; border-radius: 5px!important;
            padding: 10px 0!important; transition: 0.3s; position: relative;            
            font-weight: 400!important;
            font-size: 15px!important;
        }
        .wpcode-loop-outline-btn:hover { background: #000!important; color: #fff!important; }
        .wpcode-loop-outline-btn.loading { opacity: 0.8; 
            padding-right: 15px!important; }
        .wpcode-loop-outline-btn.added::after { content: "\e017"; font-family: WooCommerce; right: 10px!important;}

        .wpcode-loop-buynow-btn {
            display: block; width: 100%; text-align: center; margin-top: 10px;
            background: #000!important; color: #fff!important;
            border: 1px solid #000!important; border-radius: 5px!important;
            padding: 10px 0!important; transition: 0.3s; text-decoration: none!important;
            font-weight: 400!important;
            font-size: 15px!important;
        }
        .wpcode-loop-buynow-btn:hover { opacity: 0.9; color: #fff!important; }
        
        /* Hide Default View Cart */
        a.added_to_cart.wc-forward { display: none !important; }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        
        // 1. Quantity Button Logic (+/-)
        $(document).on('click', '.qty-control', function() {
            var $btn = $(this);
            var $input = $btn.siblings('input.qty');
            
            // Force values to numbers (Fixes random number generation)
            var val  = parseFloat($input.val()) || 0;
            var step = parseFloat($input.attr('step')) || 1; 
            var min  = parseFloat($input.attr('min')) || 1;
            var max  = parseFloat($input.attr('max')); 

            if ($btn.hasClass('plus')) {
                // If max is undefined (NaN) OR val is less than max
                if ( isNaN(max) || val < max ) { 
                    $input.val(val + step).trigger('change'); 
                }
            } else {
                if ( val > min ) { 
                    $input.val(val - step).trigger('change'); 
                }
            }
        });

        // 2. Buy Now - DIRECT JS REDIRECT (Bypasses Form/AJAX issues)
        $(document).on('click', '.wpcode-sp-buy-now-trigger', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $form = $btn.closest('form.wpcode-sp-form');
            var $qtyInput = $form.find('input.qty');
            
            // Get Data
            var product_id = $btn.data('product-id');
            var checkout_url = $btn.data('checkout-url');
            
            // Get Quantity (Default to 1 if sold individually or hidden)
            var quantity = 1;
            if ($qtyInput.length) {
                quantity = $qtyInput.val();
            }

            // Construct Direct URL: /checkout/?add-to-cart=ID&quantity=QTY
            // Note: If URL already has ?, use &
            var separator = checkout_url.indexOf('?') !== -1 ? '&' : '?';
            var final_url = checkout_url + separator + 'add-to-cart=' + product_id + '&quantity=' + quantity;

            // Go there
            window.location.href = final_url;
        });

    });
    </script>
    <?php
}
