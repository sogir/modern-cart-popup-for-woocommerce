/*
 * 1. AJAXIFY SINGLE ADD TO CART (Standard)
 */
add_action( 'wp_footer', 'ajaxify_single_add_to_cart_script', 99 );

function ajaxify_single_add_to_cart_script() {
    if ( ! is_product() ) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('form.cart').on('submit', function(e) {
            if( $(this).find('input[name="wpcode_buy_now_flag"]').val() === '1' ) return;
            e.preventDefault();

            var $form = $(this);
            var $btn  = $form.find('button[type="submit"]');

            if( $btn.is('.disabled') || $btn.is('.loading') ) return;
            $btn.addClass('loading');

            var formData = new FormData($form[0]);
            formData.append('add-to-cart', $btn.val());

            var ajaxUrl = (typeof wc_add_to_cart_params !== 'undefined') 
                ? wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' ) 
                : '/?wc-ajax=add_to_cart';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $btn.removeClass('loading');
                    if ( response.error && response.product_url ) {
                        window.location = response.product_url;
                        return;
                    }
                    var triggerCartUpdate = function( frags, hash ) {
                        $( document.body ).trigger( 'added_to_cart', [ frags, hash, $btn ] );
                    };
                    if ( response.fragments && ( response.fragments['#fmc-pill-data'] || response.fragments['.pill-data'] ) ) {
                        triggerCartUpdate( response.fragments, response.cart_hash );
                    } else {
                        var refreshUrl = (typeof wc_add_to_cart_params !== 'undefined') 
                            ? wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_refreshed_fragments' ) 
                            : '/?wc-ajax=get_refreshed_fragments';
                        $.ajax({
                            url: refreshUrl,
                            type: 'POST',
                            success: function( fragResponse ) {
                                if( fragResponse && fragResponse.fragments ) {
                                    triggerCartUpdate( fragResponse.fragments, fragResponse.cart_hash );
                                }
                            }
                        });
                    }
                },
                error: function() { window.location.reload(); }
            });
        });
    });
    </script>
    <?php
}

/*
 * 2. FLOATING CART CORE
 */
add_action( 'wp_footer', 'render_interactive_floating_cart' );

function render_interactive_floating_cart() {
    if ( is_checkout() || is_cart() ) return; 
    if ( ! function_exists( 'WC' ) ) return;
    
    // Initial server-side empty check
    $is_hidden_style = WC()->cart->is_empty() ? 'style="display:none;"' : '';
    ?>

    <!-- WRAPPER -->
    <div id="floating-cart-wrapper" <?php echo $is_hidden_style; ?>>
        
        <!-- MINI CART CONTAINER -->
        <div class="floating-mini-cart-container">
            <?php echo get_interactive_mini_cart_html(); ?>
        </div>

        <!-- MAIN PILL BUTTON -->
        <div class="shiny-pill-button">
            <!-- Link goes directly to Checkout -->
            <a href="<?php echo wc_get_checkout_url(); ?>" class="pill-main-link">
                <!-- Icon -->
                <svg class="pill-icon-svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                     <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
                <span class="pill-divider">|</span>
                <!-- Text Data -->
                <span class="pill-data" id="fmc-pill-data">
                   <?php echo get_custom_pill_text(); ?>
                </span>
                <!-- Desktop Arrow: Right Arrow -->
                <span class="pill-arrow-desktop">&rarr;</span>
            </a>
            
            <!-- Mobile Expand Icon -->
            <div class="mobile-expand-btn">
                <svg class="chevron-icon" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
            </div>
        </div>
    </div>

    <style>
        /* 1. Base Wrapper */
        #floating-cart-wrapper {
            position: fixed; bottom: 30px; right: 30px; z-index: 999999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            transition: opacity 0.3s;
        }

        /* 2. Shiny Pill Button */
        .shiny-pill-button {
            display: flex; align-items: center; position: relative;
            background: #000; border-radius: 50px; padding: 1px; 
            background-image: linear-gradient(#000, #000), linear-gradient(110deg, #000 30%, #fff 50%, #000 70%);
            background-origin: border-box; background-clip: content-box, border-box;
            background-size: 200% 100%; animation: shineBorder 3s linear infinite;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            transition: transform 0.2s ease;
        }
        @keyframes shineBorder { 0% { background-position: 100% 0; } 100% { background-position: -100% 0; } }
        .shiny-pill-button:hover { transform: scale(1.02); }

        /* Links & Text */
        .pill-main-link {
            display: flex; align-items: center; text-decoration: none; color: #fff;
            padding: 12px 0 12px 20px; padding-right: 20px; height: 100%; cursor: pointer;
        }
        .pill-icon-svg { margin-right: 8px; fill: #fff; }
        .pill-divider { opacity: 0.3; margin-right: 8px; color: #fff; }
        .pill-data { font-weight: 700; font-size: 14px; white-space: nowrap; color: #fff; min-width: 80px; }
        
        .pill-arrow-desktop { margin-left: 10px; color: #fff; font-size: 18px; line-height: 1; }

        .mobile-expand-btn {
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            padding: 0 15px; height: 48px; border-left: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.05); border-radius: 0 50px 50px 0;
        }
        .mobile-expand-btn svg { stroke: #fff !important; transition: transform 0.3s; }
        
        @media (min-width: 769px) {
            .mobile-expand-btn { display: none !important; }
            .pill-main-link { padding-right: 25px; border-radius: 50px; }
        }

        /* 3. Mini Cart Container */
        .floating-mini-cart-container {
            position: absolute; 
            bottom: 100%; 
            right: 0; 
            width: 400px;
            padding-bottom: 25px; /* Hover Bridge */
            background: transparent; 
            
            /* ANIMATION STATE: HIDDEN */
            /* This scale+translate creates the "Pop Up" effect when it becomes visible */
            opacity: 0; 
            visibility: hidden; 
            transform: translateY(20px) scale(0.9);
            
            /* Smooth transition for every popup */
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            
            pointer-events: none; 
            display: flex; flex-direction: column;
        }
        
        /* 4. The Visible Card */
        .fmc-card {
            background: #fff; color: #333; 
            border-radius: 12px;
            border: 2px solid #000;
            box-shadow: 0 -5px 30px rgba(0,0,0,0.15); 
            overflow: hidden;
            display: flex; flex-direction: column;
        }

        /* OPEN STATES (Via Hover or Class) */
        
        /* Only allow CSS hover on Desktop to prevent mobile conflict */
        @media (min-width: 769px) {
            #floating-cart-wrapper:hover .floating-mini-cart-container {
                opacity: 1; visibility: visible; 
                transform: translateY(0) scale(1); /* Pop Up Animation */
                pointer-events: auto;
            }
        }

        /* JS Class (Used for Mobile & Auto-Open) */
        #floating-cart-wrapper.is-open .floating-mini-cart-container {
            opacity: 1 !important; 
            visibility: visible !important; 
            transform: translateY(0) scale(1) !important; /* Pop Up Animation */
            pointer-events: auto !important;
        }

        /* --- TABLE / LIST STYLING --- */
        .fmc-header { 
            font-size: 15px; font-weight: 700; color: #222; background: #fff;
            padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid #f0f0f0; 
        }
        .fmc-close-icon { font-size: 24px; line-height: 1; color: #888; cursor: pointer; padding: 0 5px; }
        .fmc-close-icon:hover { color: #000; }

        .fmc-list { list-style: none; margin: 0; padding: 0; max-height: 300px; overflow-y: auto; background: #fff; }

        .fmc-item { 
            display: grid; 
            grid-template-columns: 1fr auto auto min-content; 
            gap: 12px; align-items: center; padding: 15px 20px; border-bottom: 1px solid #eee; 
        }
        .fmc-item:last-child { border-bottom: none; }

        .fmc-name { font-weight: 600; font-size: 14px; color: #333; line-height: 1.3; }
        .fmc-name a { text-decoration: none; color: inherit; }

        .fmc-actions { display: flex; align-items: center; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; height: 32px; }
        .fmc-btn-action { 
            display: flex; align-items: center; justify-content: center; width: 28px; height: 100%;
            background: #f9f9f9; color: #555 !important; text-decoration: none; font-weight: bold; font-size: 16px;
        }
        .fmc-qty-num { padding: 0 8px; font-size: 13px; font-weight: 600; min-width: 20px; text-align: center; }

        .fmc-btn-remove {
            color: #ff5555; text-decoration: none; font-size: 20px; 
            line-height: 1; cursor: pointer; padding: 5px;
            display: flex; align-items: center; justify-content: center;
        }
        .fmc-btn-remove:hover { color: #cc0000; transform: scale(1.1); }

        .fmc-price { 
            text-align: right; font-size: 14px; 
            display: flex; flex-direction: column; justify-content: center; align-items: flex-end;
            min-width: 60px;
        }
        .fmc-price del, .fmc-price .woocommerce-Price-amount:first-child:not(:last-child) { 
            display: block !important; font-size: 11px; color: #aaa; text-decoration: line-through; margin-bottom: 2px;
        }
        .fmc-price ins, .fmc-price .woocommerce-Price-amount:last-child { 
            display: block !important; font-weight: 800; color: #000; text-decoration: none; 
        }

        .fmc-footer { padding: 20px; background: #f9f9f9; border-top: 2px solid #f0f0f0; }
        .fmc-subtotal-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-weight: 800; color: #000; }
        .fmc-checkout-btn { 
            display: block; width: 100%; padding: 14px 0; background: #000; color: #fff !important; 
            font-weight: 700; font-size: 14px; text-transform: uppercase; text-decoration: none; text-align: center; border-radius: 4px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            #floating-cart-wrapper { bottom: 15px; right: 15px; left: 15px; width: auto; display: flex; justify-content: flex-end; }
            .pill-arrow-desktop { display: none; }
            .floating-mini-cart-container { 
                width: 100%; max-width: 100%; right: 0; bottom: 70px; 
                transform: translateY(20px); padding-bottom: 0;
            }
        }

        .do-shake .shiny-pill-button { animation: cartShake 0.5s ease-in-out !important; }
        @keyframes cartShake { 0% { transform: translateX(0); } 25% { transform: translateX(-5px) rotate(-5deg); } 50% { transform: translateX(5px) rotate(5deg); } 75% { transform: translateX(-5px) rotate(-5deg); } 100% { transform: translateX(0); } }
    </style>

    <!-- JS LOGIC -->
    <script type="text/javascript">
    jQuery(document).ready(function($){
        
        var autoCloseTimer;
        var emptyCartTimer; 
        var $wrapper = $('#floating-cart-wrapper');

        // Helper: Logic to Show or Hide Wrapper
        function checkCartState() {
            var isEmpty = $wrapper.find('.fmc-list').length === 0;

            if ( isEmpty ) {
                // If empty, wait 2 seconds then fade out
                if ( !emptyCartTimer ) {
                    emptyCartTimer = setTimeout(function(){
                        $wrapper.fadeOut();
                    }, 2000); 
                }
            } else {
                clearTimeout(emptyCartTimer);
                emptyCartTimer = null;
                // Fade in if hidden
                if ( !$wrapper.is(':visible') ) {
                    $wrapper.fadeIn();
                }
            }
        }

        // Events: Add/Remove/Update
        $(document.body).on('added_to_cart removed_from_cart updated_cart_totals wc_fragments_refreshed', function(e, fragments){
            
            var $miniCart = $wrapper.find('.floating-mini-cart-container');
            if( fragments ) {
                if( fragments['.floating-mini-cart-container'] ) $miniCart.replaceWith( fragments['.floating-mini-cart-container'] );
                if( fragments['#fmc-pill-data'] ) $('#fmc-pill-data').replaceWith( fragments['#fmc-pill-data'] );
            }

            // Run Visibility Logic
            checkCartState();

            // Shake Animation if visible
            if ( $wrapper.is(':visible') ) {
                $wrapper.addClass('do-shake');
                setTimeout(function(){ $wrapper.removeClass('do-shake'); }, 500);
            }

            // Auto-open logic when adding items
            if ( e.type === 'added_to_cart' ) {
                clearTimeout(autoCloseTimer);
                $wrapper.addClass('is-open');
                autoCloseTimer = setTimeout(function(){
                    // Only auto-close if not hovering (desktop)
                    if( ! $wrapper.is(':hover') ) $wrapper.removeClass('is-open');
                }, 4000);
            }
        });

        // Hover Logic (Desktop Only)
        $(document).on('mouseenter', '#floating-cart-wrapper', function() {
            // FIX: Disable this logic on mobile to prevent "Double Click" issue
            if( window.matchMedia('(max-width: 768px)').matches ) return;
            
            clearTimeout(autoCloseTimer);
            $(this).addClass('is-open');
        });
        
        $(document).on('mouseleave', '#floating-cart-wrapper', function() {
            autoCloseTimer = setTimeout(function(){ $wrapper.removeClass('is-open'); }, 800);
        });

        // Toggle (Mobile)
        $(document).on('click', '.mobile-expand-btn', function(e){
            e.preventDefault(); 
            e.stopPropagation(); // Stop bubbling to prevent conflicts
            $wrapper.toggleClass('is-open');
        });
        
        // Close Button Logic
        $(document).on('click', '.fmc-close-icon', function(e){
            e.preventDefault(); 
            e.stopPropagation(); 
            $wrapper.removeClass('is-open');
        });

        // AJAX Update (Qty & Remove)
        $(document).on('click', '.fmc-btn-action, .fmc-btn-remove', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            var $btn = $(this);
            var key = $btn.data('key');
            var qty = parseInt($btn.data('qty'));
            var action = $btn.data('action');
            
            if( !key ) return;

            $btn.closest('.fmc-item').css('opacity', '0.5');

            var new_qty = 0; 
            if ( $btn.hasClass('fmc-btn-action') ) {
                new_qty = (action === 'plus') ? qty + 1 : qty - 1;
            }

            $.ajax({
                type: 'POST', 
                url: wc_add_to_cart_params.ajax_url,
                data: { action: 'fmc_update_qty', cart_item_key: key, quantity: new_qty },
                success: function(res) {
                    if ( res.success ) {
                        $( document.body ).trigger( 'added_to_cart', [ res.data.fragments, res.data.cart_hash ] );
                    } else { window.location.reload(); }
                },
                error: function() { window.location.reload(); }
            });
        });

    });
    </script>
    <?php
}

/* PHP HELPERS */

function get_custom_pill_text() {
    if( ! WC()->cart ) return '';
    $count = WC()->cart->get_cart_contents_count();
    $total = WC()->cart->get_cart_total(); 
    return "{$count} Items &bull; " . strip_tags($total);
}

function get_interactive_mini_cart_html() {
    if ( ! WC()->cart || WC()->cart->is_empty() ) {
        return '<div class="fmc-card"><div style="padding:20px;text-align:center;">Your cart is empty.</div></div>';
    }

    $html = '<div class="fmc-card">';
    $html .= '<div class="fmc-header"><span>Shopping Cart</span><span class="fmc-close-icon">&times;</span></div>';
    $html .= '<ul class="fmc-list">';
    
    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 ) {
            $product_name = $_product->get_name();
            $price = WC()->cart->get_product_price( $_product );
            $qty = $cart_item['quantity'];

            $html .= '<li class="fmc-item">';
            $html .= '<div class="fmc-name"><a href="'.esc_url($_product->get_permalink()).'">' . $product_name . '</a></div>';
            
            $html .= '<div class="fmc-actions">';
            $html .= '<a href="#" class="fmc-btn-action" data-action="minus" data-key="'.$cart_item_key.'" data-qty="'.$qty.'">&minus;</a>';
            $html .= '<span class="fmc-qty-num">' . $qty . '</span>';
            $html .= '<a href="#" class="fmc-btn-action" data-action="plus" data-key="'.$cart_item_key.'" data-qty="'.$qty.'">&plus;</a>';
            $html .= '</div>';
            
            $html .= '<a href="#" class="fmc-btn-remove" data-key="'.$cart_item_key.'" title="Remove">&times;</a>';
            $html .= '<div class="fmc-price">' . $price . '</div>';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';

    $html .= '<div class="fmc-footer">';
    $html .= '<div class="fmc-subtotal-row">';
    $html .= '<span class="fmc-subtotal-label">Subtotal</span>';
    $html .= '<span class="fmc-subtotal-amount">'. WC()->cart->get_cart_subtotal() .'</span>';
    $html .= '</div>';
    $html .= '<a href="'. wc_get_checkout_url() .'" class="fmc-checkout-btn">Checkout</a>';
    $html .= '</div>'; 
    $html .= '</div>'; 

    return $html;
}

add_filter( 'woocommerce_add_to_cart_fragments', 'refresh_interactive_cart_fragments' );
function refresh_interactive_cart_fragments( $fragments ) {
    $fragments['.floating-mini-cart-container'] = '<div class="floating-mini-cart-container">' . get_interactive_mini_cart_html() . '</div>';
    $fragments['#fmc-pill-data'] = '<span class="pill-data" id="fmc-pill-data">' . get_custom_pill_text() . '</span>';
    return $fragments;
}

add_action( 'wp_ajax_fmc_update_qty', 'fmc_ajax_update_qty' );
add_action( 'wp_ajax_nopriv_fmc_update_qty', 'fmc_ajax_update_qty' );

function fmc_ajax_update_qty() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) wp_send_json_error( 'Cart Error' );
    if ( ! isset( $_POST['cart_item_key'], $_POST['quantity'] ) ) wp_send_json_error( 'Missing Data' );
    
    $key = sanitize_text_field( $_POST['cart_item_key'] );
    $qty = intval( $_POST['quantity'] );
    
    if ( $qty > 0 ) {
        WC()->cart->set_quantity( $key, $qty ); 
    } else {
        WC()->cart->remove_cart_item( $key );
    }
    
    WC()->cart->calculate_totals();
    WC()->cart->maybe_set_cart_cookies();
    
    $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() );
    wp_send_json_success( array( 'fragments' => $fragments, 'cart_hash' => WC()->cart->get_cart_hash() ) );
}
