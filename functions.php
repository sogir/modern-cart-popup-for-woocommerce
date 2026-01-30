/*
 * 1. AJAXIFY SINGLE ADD TO CART (With Data Guarantee) - UNCHANGED
 */
add_action( 'wp_footer', 'ajaxify_single_add_to_cart_script', 99 );

function ajaxify_single_add_to_cart_script() {
    if ( ! is_product() ) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        
        $('form.cart').on('submit', function(e) {
            
            // Ignore if it's the "Buy Now" button
            if( $(this).find('input[name="wpcode_buy_now_flag"]').val() === '1' ) return;

            e.preventDefault();

            var $form = $(this);
            var $btn  = $form.find('button[type="submit"]');

            if( $btn.is('.disabled') || $btn.is('.loading') ) return;

            $btn.addClass('loading');

            var formData = new FormData($form[0]);
            formData.append('add-to-cart', $btn.val());

            // 1. Send Add to Cart Request
            $.ajax({
                url: wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' ),
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

                    // Helper: Trigger the update event for the floating cart
                    var triggerCartUpdate = function( frags, hash ) {
                        $( document.body ).trigger( 'added_to_cart', [ frags, hash, $btn ] );
                    };

                    // 2. CHECK: Did we get the custom pill data?
                    if ( response.fragments && ( response.fragments['#fmc-pill-data'] || response.fragments['.pill-data'] ) ) {
                        // Yes! Update immediately
                        triggerCartUpdate( response.fragments, response.cart_hash );
                    } else {
                        // No (The Issue). 
                        // FIX: Manually fetch fresh fragments immediately
                        $.ajax({
                            url: wc_add_to_cart_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_refreshed_fragments' ),
                            type: 'POST',
                            success: function( fragResponse ) {
                                if( fragResponse && fragResponse.fragments ) {
                                    triggerCartUpdate( fragResponse.fragments, fragResponse.cart_hash );
                                }
                            }
                        });
                    }
                },
                error: function() {
                    window.location.reload(); 
                }
            });
        });
    });
    </script>
    <?php
}

/*
 * 2. FLOATING CART CORE (Robust Sync + Auto-Open)
 */
add_action( 'wp_footer', 'render_interactive_floating_cart' );

function render_interactive_floating_cart() {
    if ( is_checkout() ) return;
    if ( ! function_exists( 'WC' ) ) return;
    
    // Initial State
    $is_hidden_style = WC()->cart->is_empty() ? 'style="display:none;"' : '';
    ?>

    <!-- WRAPPER -->
    <div id="floating-cart-wrapper" <?php echo $is_hidden_style; ?>>
        
        <!-- HOVER MINI CART LIST -->
        <div class="floating-mini-cart-container">
            <?php echo get_interactive_mini_cart_html(); ?>
        </div>

        <!-- MAIN PILL BUTTON -->
        <div class="shiny-pill-button">
            <a href="<?php echo wc_get_checkout_url(); ?>" class="pill-main-link">
                <!-- SVG Icon -->
                <svg class="pill-icon-svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                     <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
                <span class="pill-divider">|</span>
                <!-- Data Span (Updated via AJAX) -->
                <span class="pill-data" id="fmc-pill-data">
                   <?php echo get_custom_pill_text(); ?>
                </span>
                <span class="pill-arrow-desktop">&rarr;</span>
            </a>
            
            <!-- Mobile Toggle -->
            <div class="mobile-expand-btn">
                <svg class="chevron-icon" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
            </div>
        </div>
    </div>

    <!-- STYLES -->
    <style>
        /* Hide Default Notices */
        .woocommerce-message, .woocommerce-error, .woocommerce-info { display: none !important; }

        /* Wrapper */
        #floating-cart-wrapper {
            position: fixed; bottom: 30px; right: 30px; z-index: 999999;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            transition: opacity 0.3s;
        }

        /* Pill Button */
        .shiny-pill-button {
            display: flex; align-items: center; position: relative;
            background: #000; border-radius: 50px; padding: 1px; 
            background-image: linear-gradient(#000, #000), linear-gradient(110deg, #000 30%, #fff 50%, #000 70%);
            background-origin: border-box; background-clip: content-box, border-box;
            background-size: 200% 100%; animation: shineBorder 3s linear infinite;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
            transition: transform 0.2s ease;
        }
        @keyframes shineBorder { 0% { background-position: 100% 0; } 100% { background-position: -100% 0; } }
        .shiny-pill-button:hover { transform: scale(1.02); }

        /* Content */
        .pill-main-link {
            display: flex; align-items: center; text-decoration: none; color: #fff;
            padding: 12px 0 12px 20px; padding-right: 20px; height: 100%;
        }
        .pill-icon-svg { margin-right: 8px; fill: #fff; }
        .pill-divider { opacity: 0.5; margin-right: 8px; color: #fff; }
        .pill-data { font-weight: 700; font-size: 14px; white-space: nowrap; color: #fff; min-width: 80px; }
        .pill-arrow-desktop { margin-left: 8px; color: #fff; font-size: 16px; }

        .mobile-expand-btn {
            display: none; align-items: center; justify-content: center; cursor: pointer;
            padding: 0 15px; height: 48px; border-left: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
        }

        /* MINI CART CONTAINER */
        .floating-mini-cart-container {
            position: absolute; bottom: 100%; right: 0; width: 360px;
            background: #fff; color: #333; border-radius: 12px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.2); margin-bottom: 15px; padding: 0;
            opacity: 0; visibility: hidden; transform: translateY(10px);
            transition: all 0.3s ease; pointer-events: none;
            overflow: hidden; /* For rounded corners */
            display: flex; flex-direction: column;
        }
        /* Hover Bridge */
        .floating-mini-cart-container::before {
            content: ''; position: absolute; top: 100%; left: 0; width: 100%; height: 20px; background: transparent;
        }

        /* Desktop Hover */
        @media (min-width: 769px) {
            #floating-cart-wrapper:hover .floating-mini-cart-container {
                opacity: 1; visibility: visible; transform: translateY(0); pointer-events: auto;
            }
        }
        
        /* Force Open */
        .floating-mini-cart-container.force-open {
            opacity: 1 !important; visibility: visible !important; transform: translateY(0) !important; pointer-events: auto !important;
        }
        
        /* HEADER */
        .fmc-header { 
            font-size: 14px; font-weight: 700; color: #333; 
            background: #f9f9f9;
            padding: 15px; 
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #eee; 
        }
        .fmc-close-icon {
            cursor: pointer; font-size: 20px; line-height: 1; color: #999;
            width: 24px; height: 24px; text-align: center;
            transition: color 0.2s;
        }
        .fmc-close-icon:hover { color: #ff4444; }

        /* LIST */
        .fmc-list { list-style: none; margin: 0; padding: 15px; max-height: 250px; overflow-y: auto; }
        .fmc-item { display: grid; grid-template-columns: 1fr auto auto; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 13px; border-bottom: 1px dashed #eee; padding-bottom: 8px; }
        .fmc-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .fmc-name { font-weight: 600; color: #333; line-height: 1.2; }
        .fmc-actions { display: flex; align-items: center; gap: 5px; background: #f5f5f5; padding: 2px 6px; border-radius: 4px; }
        .fmc-qty-num { font-weight: bold; min-width: 15px; text-align: center; }
        .fmc-btn-action { text-decoration: none; display: inline-flex; justify-content: center; align-items: center; width: 18px; height: 18px; border-radius: 50%; font-weight: bold; font-size: 12px; background: #fff; color: #000 !important; border: 1px solid #ccc; cursor: pointer; }
        .fmc-btn-action:hover { background: #000; color: #fff !important; }
        .fmc-btn-remove { color: #ff4444; font-size: 16px; margin-left: 5px; text-decoration: none; }
        
        /* FOOTER & TOTALS */
        .fmc-footer {
            padding: 15px;
            background: #fff;
            border-top: 1px solid #eee;
        }
        .fmc-subtotal-row {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 15px;
            font-size: 16px; font-weight: 800; color: #000;
        }
        .fmc-subtotal-label { color: #555; font-size: 14px; font-weight: 600; }
        
        .fmc-checkout-btn { display: block; width: 100%; padding: 12px 0; border: 1px solid #000; border-radius: 50px; background: #000; color: #fff !important; font-weight: 700; font-size: 13px; text-transform: uppercase; text-decoration: none; text-align: center; transition: opacity 0.2s; }
        .fmc-checkout-btn:hover { opacity: 0.8; }

        /* Mobile */
        @media (max-width: 768px) {
            #floating-cart-wrapper { bottom: 20px; right: 20px; }
            .pill-arrow-desktop { display: none; }
            .mobile-expand-btn { display: flex; }
            .pill-main-link { padding-right: 10px; }
            
            /* Mobile Positioning Fix */
            .floating-mini-cart-container { 
                display: none; 
                width: 90vw; 
                max-width: 360px; 
                right: 0; 
                bottom: calc(100% + 10px); /* Sits right above the pill with small gap */
                margin-bottom: 0;
                transform: none; 
                visibility: visible; 
                opacity: 1; 
            }
            
            #floating-cart-wrapper.mobile-open .floating-mini-cart-container { display: flex !important; pointer-events: auto; animation: fadeUp 0.3s ease; }
            #floating-cart-wrapper.mobile-open .chevron-icon { transform: rotate(180deg); }
            @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        }

        .do-shake .shiny-pill-button { animation: cartShake 0.5s ease-in-out !important; }
        @keyframes cartShake { 0% { transform: translateX(0); } 25% { transform: translateX(-5px) rotate(-5deg); } 50% { transform: translateX(5px) rotate(5deg); } 75% { transform: translateX(-5px) rotate(-5deg); } 100% { transform: translateX(0); } }
    </style>

    <!-- JS LOGIC -->
    <script type="text/javascript">
    jQuery(document).ready(function($){
        
        var autoCloseTimer;

        // 1. LISTEN FOR EVENTS (Added + Refreshed)
        // 'wc_fragments_refreshed' ensures we update even if 'added_to_cart' missed the data
        $(document.body).on('added_to_cart removed_from_cart updated_cart_totals wc_fragments_refreshed', function(e, fragments, cart_hash, $button){
            
            var $wrapper = $('#floating-cart-wrapper');
            var $miniCart = $wrapper.find('.floating-mini-cart-container');
            
            if( fragments ) {
                // A. UPDATE THE LIST
                if( fragments['.floating-mini-cart-container'] ) {
                    $miniCart.replaceWith( fragments['.floating-mini-cart-container'] );
                    $miniCart = $wrapper.find('.floating-mini-cart-container'); // Re-select
                }
                // B. UPDATE THE PILL TEXT
                if( fragments['#fmc-pill-data'] ) {
                    $('#fmc-pill-data').replaceWith( fragments['#fmc-pill-data'] );
                } else if ( fragments['.pill-data'] ) {
                    $('#fmc-pill-data').replaceWith( fragments['.pill-data'] );
                }
            }

            // C. VISUALS (Shake & Show)
            $wrapper.fadeIn();
            $wrapper.addClass('do-shake');
            setTimeout(function(){ $wrapper.removeClass('do-shake'); }, 500);

            // D. AUTO OPEN LOGIC (Only on Add/Update, not just generic refresh if invisible)
            if ( e.type === 'added_to_cart' ) {
                clearTimeout(autoCloseTimer);
                $miniCart.addClass('force-open');
                $wrapper.addClass('mobile-open');

                autoCloseTimer = setTimeout(function(){
                    if( ! $wrapper.is(':hover') ) {
                        $wrapper.find('.floating-mini-cart-container').removeClass('force-open');
                        $wrapper.removeClass('mobile-open');
                    }
                }, 4000);
            }
            
            // Check emptiness
            if( $('.fmc-item').length === 0 && !fragments ) {
                 $wrapper.fadeOut();
            }
        });

        // 2. CANCEL AUTO-CLOSE ON HOVER
        $(document).on('mouseenter', '#floating-cart-wrapper', function() {
            clearTimeout(autoCloseTimer);
            $(this).find('.floating-mini-cart-container').addClass('force-open');
        });

        // 3. RESTART TIMER ON LEAVE
        $(document).on('mouseleave', '#floating-cart-wrapper', function() {
            var $wrapper = $(this);
            autoCloseTimer = setTimeout(function(){
                 $wrapper.find('.floating-mini-cart-container').removeClass('force-open');
            }, 1000);
        });

        // 4. Mobile Toggle
        $(document).on('click', '.mobile-expand-btn', function(e){
            e.preventDefault(); e.stopPropagation(); 
            $('#floating-cart-wrapper').toggleClass('mobile-open');
        });

        // 5. Close on Click Outside or Close Icon
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#floating-cart-wrapper').length) {
                $('#floating-cart-wrapper').removeClass('mobile-open');
                $('.floating-mini-cart-container').removeClass('force-open');
            }
        });

        // NEW: Close Icon Click
        $(document).on('click', '.fmc-close-icon', function(e){
            e.preventDefault();
            $('#floating-cart-wrapper').removeClass('mobile-open');
            $('.floating-mini-cart-container').removeClass('force-open');
        });

        // 6. Qty Buttons Logic
        $(document).on('click', '.fmc-btn-action', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var key = $btn.data('key');
            var qty = parseInt($btn.data('qty'));
            var new_qty = ($btn.data('action') === 'plus') ? qty + 1 : qty - 1;

            if( new_qty < 1 ) {
                 $('.fmc-btn-remove[data-cart_item_key="' + key + '"]').trigger('click');
                 return;
            }

            // Visual feedback
            $btn.closest('.fmc-item').css('opacity', '0.5');

            $.ajax({
                type: 'POST', url: wc_add_to_cart_params.ajax_url,
                data: { action: 'fmc_update_qty', cart_item_key: key, quantity: new_qty },
                success: function(res) {
                    if ( res.success ) {
                        $( document.body ).trigger( 'added_to_cart', [ res.data.fragments, res.data.cart_hash ] );
                    }
                },
                complete: function() { 
                    $btn.closest('.fmc-item').css('opacity', '1'); 
                }
            });
        });

    });
    </script>
    <?php
}

/* --- PHP FRAGMENT HELPERS --- */

// 1. Text Format
function get_custom_pill_text() {
    if( ! WC()->cart ) return '';
    $count = WC()->cart->get_cart_contents_count();
    $total = WC()->cart->get_cart_total();
    return "{$count} Items - {$total}";
}

// 2. Mini Cart HTML (RESTRUCTURED)
function get_interactive_mini_cart_html() {
    if ( ! WC()->cart || WC()->cart->is_empty() ) return '<div style="padding:15px;text-align:center;">Cart is empty</div>';

    // Header with Close Icon
    $html = '<div class="fmc-header">';
    $html .= '<span>Your Cart</span>';
    $html .= '<span class="fmc-close-icon">&times;</span>';
    $html .= '</div>';

    // Items List
    $html .= '<ul class="fmc-list">';
    
    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 ) {
            $product_name = $_product->get_name();
            $price = WC()->cart->get_product_price( $_product );
            $qty = $cart_item['quantity'];
            $remove = wc_get_cart_remove_url( $cart_item_key );

            $html .= '<li class="fmc-item">';
            $html .= '<div class="fmc-info"><span class="fmc-name">' . $product_name . '</span></div>';
            $html .= '<div class="fmc-actions">';
            $html .= '<a href="#" class="fmc-btn-action" data-action="minus" data-key="'.$cart_item_key.'" data-qty="'.$qty.'">-</a>';
            $html .= '<span class="fmc-qty-num">' . $qty . '</span>';
            $html .= '<a href="#" class="fmc-btn-action" data-action="plus" data-key="'.$cart_item_key.'" data-qty="'.$qty.'">+</a>';
            $html .= '<a href="'.$remove.'" class="remove fmc-btn-remove" data-cart_item_key="'.$cart_item_key.'">&times;</a>';
            $html .= '</div>';
            $html .= '<div class="fmc-price">' . $price . '</div>';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';

    // Footer with Prominent Total
    $html .= '<div class="fmc-footer">';
    $html .= '<div class="fmc-subtotal-row">';
    $html .= '<span class="fmc-subtotal-label">Subtotal:</span>';
    $html .= '<span class="fmc-subtotal-amount">'. WC()->cart->get_cart_subtotal() .'</span>';
    $html .= '</div>';
    $html .= '<a href="'. wc_get_checkout_url() .'" class="fmc-checkout-btn">Complete Order</a>';
    $html .= '</div>'; // End footer

    return $html;
}

// 3. Register Fragments
add_filter( 'woocommerce_add_to_cart_fragments', 'refresh_interactive_cart_fragments' );
function refresh_interactive_cart_fragments( $fragments ) {
    $fragments['.floating-mini-cart-container'] = '<div class="floating-mini-cart-container">' . get_interactive_mini_cart_html() . '</div>';
    $fragments['#fmc-pill-data'] = '<span class="pill-data" id="fmc-pill-data">' . get_custom_pill_text() . '</span>';
    return $fragments;
}

// 4. Qty Handler (FIXED)
add_action( 'wp_ajax_fmc_update_qty', 'fmc_ajax_update_qty' );
add_action( 'wp_ajax_nopriv_fmc_update_qty', 'fmc_ajax_update_qty' );

function fmc_ajax_update_qty() {
    if ( ! isset( $_POST['cart_item_key'], $_POST['quantity'] ) ) wp_send_json_error();
    
    $key = sanitize_text_field( $_POST['cart_item_key'] );
    $qty = intval( $_POST['quantity'] );
    
    if ( $qty > 0 ) {
        WC()->cart->set_quantity( $key, $qty ); 
    }
    
    WC()->cart->calculate_totals();
    WC()->cart->maybe_set_cart_cookies();
    
    // FIX: Do not call woocommerce_cart_fragments() directly as it prints output.
    // Instead, get the fragments via filter.
    $fragments = apply_filters( 'woocommerce_add_to_cart_fragments', array() );
    
    wp_send_json_success( array( 
        'fragments' => $fragments, 
        'cart_hash' => WC()->cart->get_cart_hash() 
    ) );
}
