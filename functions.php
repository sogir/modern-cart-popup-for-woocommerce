/*
 * FLOATING CART: Outline Button + Split Action Mobile + SVG Icons + Text Rotator
 */

add_action( 'wp_footer', 'render_interactive_floating_cart' );

function render_interactive_floating_cart() {
    // Hide on Checkout Page
    if ( is_checkout() ) return;
    
    if ( ! function_exists( 'WC' ) ) return;
    
    // Initial hidden state
    $is_hidden_style = WC()->cart->is_empty() ? 'style="display:none;"' : '';
    ?>

    <!-- WRAPPER -->
    <div id="floating-cart-wrapper" <?php echo $is_hidden_style; ?>>
        
        <!-- HOVER MINI CART (Hidden on Mobile until toggled) -->
        <div class="floating-mini-cart-container">
            <?php echo get_interactive_mini_cart_html(); ?>
        </div>

        <!-- MAIN PILL BUTTON -->
        <div class="shiny-pill-button">
            
            <!-- LEFT SIDE: Link to Checkout -->
            <a href="<?php echo wc_get_checkout_url(); ?>" class="pill-main-link">
                <!-- SVG CART ICON -->
                <svg class="pill-icon-svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                     <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
                
                <span class="pill-divider">|</span>
                
                <!-- We add a wrapper ID to target text swapping specifically -->
                <span class="pill-data" id="fmc-pill-data">
                   <?php echo get_custom_pill_text(); ?>
                </span>

                <!-- Desktop Arrow (Visual Only) -->
                <span class="pill-arrow-desktop">&rarr;</span>
            </a>

            <!-- RIGHT SIDE: Mobile Expand Button (Split Action) -->
            <div class="mobile-expand-btn">
                <!-- SVG CHEVRON -->
                <svg class="chevron-icon" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
            </div>

        </div>
    </div>

    <!-- STYLES -->
    <style>
        /* --- WRAPPER --- */
        #floating-cart-wrapper {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        /* --- SHINY PILL CONTAINER --- */
        .shiny-pill-button {
            display: flex;
            align-items: center;
            position: relative;
            background: #000;
            border-radius: 50px;
            
            /* UPDATED: Border back to 1px */
            padding: 1px; 
            
            /* Animated Shine Gradient */
            background-image: linear-gradient(#000, #000), 
                              linear-gradient(110deg, #000 30%, #fff 50%, #000 70%);
            background-origin: border-box;
            background-clip: content-box, border-box;
            background-size: 200% 100%;
            animation: shineBorder 3s linear infinite;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
            transition: transform 0.2s ease;
            overflow: hidden; 
        }

        @keyframes shineBorder {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        /* SHAKE ANIMATION */
        @keyframes cartShake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px) rotate(-5deg); }
            50% { transform: translateX(5px) rotate(5deg); }
            75% { transform: translateX(-5px) rotate(-5deg); }
            100% { transform: translateX(0); }
        }

        .do-shake .shiny-pill-button {
            animation: cartShake 0.5s ease-in-out !important;
        }
        
        .shiny-pill-button:hover { transform: scale(1.02); }

        /* --- LEFT SIDE: LINK --- */
        .pill-main-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #fff;
            padding: 12px 0 12px 20px; 
            padding-right: 20px;
            height: 100%;
        }

        .pill-icon-svg { margin-right: 8px; fill: #fff; }
        .pill-divider { opacity: 0.5; font-weight: 300; margin-right: 8px; color: #fff; }
        
        .pill-data {
            font-weight: 700;
            font-size: 14px;
            white-space: nowrap;
            color: #fff;
            min-width: 80px; /* Prevents jitter during text swap */
            transition: opacity 0.3s ease;
        }
        
        .pill-arrow-desktop { margin-left: 8px; color: #fff; font-size: 16px; }

        /* --- RIGHT SIDE: MOBILE EXPAND BTN --- */
        .mobile-expand-btn {
            display: none; 
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0 15px; 
            height: 48px; 
            border-left: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
            transition: background 0.2s;
        }
        
        .mobile-expand-btn:active { background: rgba(255,255,255,0.2); }

        .chevron-icon {
            stroke: #fff;
            width: 20px;
            height: 20px;
            transition: transform 0.3s ease;
        }

        /* --- MINI CART CONTAINER --- */
        .floating-mini-cart-container {
            position: absolute;
            bottom: 100%;
            right: 0;
            width: 360px;
            background: #fff;
            color: #333;
            border-radius: 12px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px; 
            padding: 15px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .floating-mini-cart-container::before {
            content: ''; position: absolute; top: 100%; left: 0; width: 100%; height: 25px; background: transparent;
        }

        @media (min-width: 769px) {
            #floating-cart-wrapper:hover .floating-mini-cart-container {
                opacity: 1; visibility: visible; transform: translateY(0); pointer-events: auto;
            }
        }

        .floating-mini-cart-container::after {
            content: ''; position: absolute; bottom: -6px; right: 40px; width: 12px; height: 12px; background: #fff; transform: rotate(45deg);
        }

        /* --- LIST STYLES --- */
        .fmc-header {
            font-size: 11px; text-transform: uppercase; font-weight: 700; color: #999;
            margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;
            display: flex; justify-content: space-between;
        }
        .fmc-list { list-style: none; margin: 0; padding: 0; max-height: 250px; overflow-y: auto; }
        .fmc-item {
            display: grid; grid-template-columns: 1fr auto auto; align-items: center;
            gap: 10px; margin-bottom: 12px; font-size: 13px; border-bottom: 1px dashed #eee; padding-bottom: 8px;
        }
        .fmc-item:last-child { border-bottom: none; margin-bottom: 0; }
        
        .fmc-info { display: flex; flex-direction: column; }
        .fmc-name { font-weight: 600; color: #333; line-height: 1.2; margin-bottom: 2px;}
        .fmc-meta { font-size: 11px; color: #777; }

        .fmc-actions {
            display: flex; align-items: center; gap: 5px;
            background: #f5f5f5; padding: 2px 6px; border-radius: 4px;
        }
        .fmc-qty-num { font-weight: bold; min-width: 15px; text-align: center; }
        
        .fmc-btn-action {
            text-decoration: none; display: inline-flex; justify-content: center; align-items: center;
            width: 18px; height: 18px; border-radius: 50%;
            font-weight: bold; font-size: 12px;
            background: #fff; color: #000; border: 1px solid #ccc;
            cursor: pointer; transition: 0.2s;
        }
        .fmc-btn-action:hover { background: #000; color: #fff!important; border-color: #000; }
        .fmc-btn-remove { color: #ff4444; font-size: 16px; margin-left: 5px; text-decoration: none; }

        .fmc-price { 
            font-weight: 700; color: #000; font-size: 13px; text-align: right; min-width: 60px;
            display: flex; flex-direction: column; align-items: flex-end;
        }
        .fmc-price del { color: #999; font-size: 11px; font-weight: normal; opacity: 0.8; margin-bottom: -2px; display: block; }
        .fmc-price ins { text-decoration: none; display: block; }
        
        /* FOOTER AREA */
        .fmc-footer-wrapper { margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; text-align: center; }
        .fmc-savings { font-size: 12px; color: #666; margin-bottom: 8px; }
        .fmc-savings span { color: #008a00; font-weight: 700; }

        /* CHECKOUT BUTTON - !important color */
        .fmc-checkout-btn {
            display: block; width: 100%; padding: 10px 0; border: 1px solid #000;
            border-radius: 50px; background: transparent; 
            color: #000 !important; /* UPDATED: !important added */
            font-weight: 700; font-size: 13px; text-transform: uppercase;
            text-decoration: none; transition: all 0.2s ease; box-sizing: border-box;
        }
        .fmc-checkout-btn:hover { background: #000; color: #fff !important; }

        /* --- MOBILE --- */
        @media (max-width: 768px) {
            #floating-cart-wrapper { bottom: 20px; right: 20px; left: auto; transform: none; }
            .pill-arrow-desktop { display: none; }
            .mobile-expand-btn { display: flex; }
            .pill-main-link { padding-right: 10px; }

            .floating-mini-cart-container {
                display: none; 
                width: 90vw; max-width: 360px;
                right: 0; bottom: 80px; 
                transform: none; visibility: visible; opacity: 1;
            }
            
            #floating-cart-wrapper.mobile-open .floating-mini-cart-container {
                display: block !important;
                pointer-events: auto;
                animation: fadeUp 0.3s ease;
            }

            #floating-cart-wrapper.mobile-open .chevron-icon { transform: rotate(180deg); }

            @keyframes fadeUp {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }
    </style>

    <!-- JS LOGIC -->
    <script type="text/javascript">
    jQuery(document).ready(function($){
        
        /* --- 1. TEXT ROTATOR LOGIC --- */
        var rotatorInterval;
        var cartBaseText = ''; // Stores the real cart total text
        
        function startTextRotator() {
            // Clear any existing intervals
            if (rotatorInterval) clearInterval(rotatorInterval);
            
            // Initial read of text
            cartBaseText = $('#fmc-pill-data').text().trim();

            rotatorInterval = setInterval(function() {
                var $pill = $('#fmc-pill-data');
                var current = $pill.text().trim();
                
                // Fade out
                $pill.css('opacity', 0);
                
                setTimeout(function(){
                    if ( current === cartBaseText ) {
                        // Switch to Call to Action
                        $pill.text('Complete Order');
                    } else {
                        // Switch back to Totals
                        // Ensure we use the latest global variable in case cart updated
                        $pill.text(cartBaseText); 
                    }
                    // Fade in
                    $pill.css('opacity', 1);
                }, 300); // Wait for fade out
                
            }, 6000); // Rotate every 6 seconds (not annoying)
        }

        // Start immediately
        startTextRotator();

        // 2. Mobile Expand Logic
        $(document).on('click', '.mobile-expand-btn', function(e){
            e.preventDefault();
            e.stopPropagation(); 
            var $wrapper = $('#floating-cart-wrapper');
            $wrapper.toggleClass('mobile-open');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#floating-cart-wrapper').length) {
                $('#floating-cart-wrapper').removeClass('mobile-open');
            }
        });

        // 3. Visibility, Shake & Data Refresh Logic
        $(document.body).on('added_to_cart removed_from_cart updated_cart_totals', function(e, fragments, cart_hash, $button){
            var $wrapper = $('#floating-cart-wrapper');
            
            $wrapper.addClass('do-shake');
            setTimeout(function(){ $wrapper.removeClass('do-shake'); }, 500); 

            // After AJAX updates the DOM, we need to refresh the "Base Text" for the rotator
            setTimeout(function(){
                var $pillData = $('#fmc-pill-data');
                if ($pillData.length) {
                    // Update our reference variable with the NEW total from PHP
                    cartBaseText = $pillData.text().trim();
                    // Restart rotator to reset timing
                    startTextRotator();
                }

                if( $('.fmc-item').length > 0 ) {
                    $wrapper.fadeIn();
                } else {
                    $wrapper.fadeOut();
                    $wrapper.removeClass('mobile-open');
                }
            }, 500);
        });

        // 4. Qty Update Logic
        $(document).on('click', '.fmc-btn-action', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var cart_item_key = $btn.data('key');
            var current_qty = parseInt($btn.data('qty'));
            var action = $btn.data('action');
            var new_qty = (action === 'plus') ? current_qty + 1 : current_qty - 1;

            if( new_qty < 1 ) {
                 $('.fmc-btn-remove[data-cart_item_key="' + cart_item_key + '"]').trigger('click');
                 return;
            }

            $btn.parents('.fmc-actions').css('opacity', '0.5');

            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: {
                    action: 'fmc_update_qty',
                    cart_item_key: cart_item_key,
                    quantity: new_qty
                },
                success: function(response) {
                    if ( response.success ) {
                        $( document.body ).trigger( 'added_to_cart', [ response.data.fragments, response.data.cart_hash ] );
                    }
                },
                complete: function() {
                     $btn.parents('.fmc-actions').css('opacity', '1');
                }
            });
        });
    });
    </script>
    <?php
}

// 2. Helper: Text Format
function get_custom_pill_text() {
    $count = WC()->cart->get_cart_contents_count();
    $label = 'Item'; 
    $total = WC()->cart->get_cart_total();
    return "{$count} {$label} - {$total}";
}

// 3. Helper: Generate Mini Cart HTML
function get_interactive_mini_cart_html() {
    if ( WC()->cart->is_empty() ) return '<div class="fmc-empty" style="padding:10px;text-align:center;">Cart is empty</div>';

    $html = '<div class="fmc-header"><span>Your Cart</span> <span>'. WC()->cart->get_cart_subtotal() .'</span></div>';
    $html .= '<ul class="fmc-list">';
    
    $total_savings = 0;
    $total_savings += WC()->cart->get_discount_total();
    if( WC()->cart->display_prices_including_tax() ) {
        $total_savings += WC()->cart->get_discount_tax();
    }

    $items = array_slice( WC()->cart->get_cart(), 0, 5 ); 
    
    foreach ( $items as $cart_item_key => $cart_item ) {
        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        
        if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 ) {
            
            if( $_product->is_on_sale() ) {
                $regular_price = (float) $_product->get_regular_price();
                $sale_price = (float) $_product->get_price();
                $item_saving = ($regular_price - $sale_price) * $cart_item['quantity'];
                $total_savings += $item_saving;
            }

            $product_name = $_product->get_name();
            
            if ( $_product->is_on_sale() ) {
                $price_html = wc_format_sale_price( 
                    wc_get_price_to_display( $_product, array( 'price' => $_product->get_regular_price() ) ), 
                    wc_get_price_to_display( $_product ) 
                ) . $_product->get_price_suffix();
            } else {
                $price_html = wc_price( wc_get_price_to_display( $_product ) ) . $_product->get_price_suffix();
            }
            
            $product_price = apply_filters( 'woocommerce_cart_item_price', $price_html, $cart_item, $cart_item_key );
            $qty = $cart_item['quantity'];
            $remove_url = wc_get_cart_remove_url( $cart_item_key );

            $html .= '<li class="fmc-item">';
            $html .= '<div class="fmc-info">';
            $html .= '<span class="fmc-name">' . $product_name . '</span>';
            if($_product->is_type('variation')){
                 $html .= '<span class="fmc-meta">'. wc_get_formatted_variation( $_product, true ) .'</span>';
            }
            $html .= '</div>';

            $html .= '<div class="fmc-actions">';
            $html .= '<a href="#" class="fmc-btn-action" data-action="minus" data-key="'.$cart_item_key.'" data-qty="'.$qty.'">-</a>';
            $html .= '<span class="fmc-qty-num">' . $qty . '</span>';
            $html .= '<a href="#" class="fmc-btn-action" data-action="plus" data-key="'.$cart_item_key.'" data-qty="'.$qty.'">+</a>';
            $html .= '<a href="'.$remove_url.'" class="remove fmc-btn-remove" aria-label="Remove" data-cart_item_key="'.$cart_item_key.'">&times;</a>';
            $html .= '</div>';

            $html .= '<div class="fmc-price">' . $product_price . '</div>';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';

    $html .= '<div class="fmc-footer-wrapper">';
    if ( $total_savings > 0 ) {
        $html .= '<div class="fmc-savings">You are saving <span>' . wc_price($total_savings) . '</span></div>';
    }
    $html .= '<a href="'. wc_get_checkout_url() .'" class="fmc-checkout-btn">Complete Order</a>';
    $html .= '</div>';

    return $html;
}

// 4. AJAX: Update Fragments
add_filter( 'woocommerce_add_to_cart_fragments', 'refresh_interactive_cart_fragments' );
function refresh_interactive_cart_fragments( $fragments ) {
    $fragments['.floating-mini-cart-container'] = '<div class="floating-mini-cart-container">' . get_interactive_mini_cart_html() . '</div>';
    // Ensure we use the proper ID for JS targeting
    $fragments['.pill-data'] = '<span class="pill-data" id="fmc-pill-data">' . get_custom_pill_text() . '</span>';
    return $fragments;
}

// 5. AJAX Handler for Qty
add_action( 'wp_ajax_fmc_update_qty', 'fmc_ajax_update_qty' );
add_action( 'wp_ajax_nopriv_fmc_update_qty', 'fmc_ajax_update_qty' );

function fmc_ajax_update_qty() {
    if ( ! isset( $_POST['cart_item_key'], $_POST['quantity'] ) ) wp_send_json_error();

    $cart_item_key = sanitize_text_field( $_POST['cart_item_key'] );
    $quantity      = intval( $_POST['quantity'] );

    if ( $quantity > 0 ) {
        WC()->cart->set_quantity( $cart_item_key, $quantity );
    } 
    WC()->cart->calculate_totals();
    WC()->cart->maybe_set_cart_cookies();

    $fragments = array();
    if ( function_exists( 'woocommerce_cart_fragments' ) ) {
        woocommerce_cart_fragments();
    }
    $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', $fragments );

    wp_send_json_success( array(
        'fragments' => $fragments,
        'cart_hash' => WC()->cart->get_cart_hash()
    ) );
}
