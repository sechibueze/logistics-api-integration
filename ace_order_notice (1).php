<?php



add_action( 'woocommerce_thankyou', 'notify_ace');

function notify_ace( $order_id ){
    // print_r("order_id :" . $order_id);

    // get order object and order details
    $order = new WC_Order( $order_id ); 
    $email = $order->billing_email;
    $phone = $order->billing_phone;
    $shipping_type = $order->get_shipping_method();
    $shipping_cost = $order->get_total_shipping();
    $ace_payment_method = $order->get_payment_method() == "cod" ? 2 : 1;

   

    // set the address fields
    $user_id = $order->user_id;
    $address_fields = array('country',
        'title',
        'first_name',
        'last_name',
        'company',
        'address_1',
        'address_2',
        'address_3',
        'address_4',
        'city',
        'state',
        'postcode');

    $address = array();
    if(is_array($address_fields)){
        foreach($address_fields as $field){
            $address['billing_'.$field] = get_user_meta( $user_id, 'billing_'.$field, true );
            $address['shipping_'.$field] = get_user_meta( $user_id, 'shipping_'.$field, true );
        }
    }
    
    // get coupon information (if applicable)
    $cps = array();
    $cps = $order->get_items( 'coupon' );
    
    $coupon = array();
    foreach($cps as $cp){
            // get coupon titles (and additional details if accepted by the API)
            $coupon[] = $cp['name'];
    }
    
    // get product details
    $items = $order->get_items();
    
    $item_name = array();
    $item_qty = array();
    $item_price = array();
    $item_sku = array();

    $ace_items = array();

        
    foreach( $items as $key => $item){

        $item_id = $item['product_id'];
        $product = new WC_Product($item_id);
        // $item_sku[] = $product->get_sku();

        $ace_item = array();

        $ace_item["itemId"] = $item_id;
        $ace_item["description"] = $item['name'];
        $ace_item["sku"] = $product->get_sku();
        $ace_item["price"] = $item['line_total'];
        $ace_item["quantity"] = $item['qty'];
        $ace_item["total"] = $item['line_total'] * $item['qty']; // qty x pr


        // push item to items
        array_push($ace_items, $ace_item);
        
        
    }
    
    /* for online payments, send across the transaction ID/key. If the payment is handled offline, you could send across the order key instead */
    $transaction_key = get_post_meta( $order_id, '_transaction_id', true );
    $transaction_key = empty($transaction_key) ? $_GET['key'] : $transaction_key;   
    
    

    // setup the data which has to be sent
        $ace_package = array(
                      "packageId" => $order_id,
                      "packageNumber" => $order_id, 
                      "items" => $ace_items,
                      "paymentTypeId" => $ace_payment_method, 
                      "shippingCost" => $shipping_cost,
                      "customerAltPhoneNo" => $phone 
                    );
        $packages = array();
        array_push($packages, $ace_package);
        $pay_load = array(

              "requestedPickUp" => true,
              "orderDetails" => [
                array(                
                  "firstName" => $address['shipping_first_name'], 
                  "lastName" => $address['shipping_last_name'], 
                  "orderNumber" => $order_id, 
                  "createDate" => date("Y-m-d"), 
                  "shippingAddress" => $address['shipping_address_1'], 
                  "region" => $address['shipping_state'], 
                  "city" => $address['shipping_city'],
                  "email" => $email, 
                  "phone" => $phone,
                  "altphone" => $phone, 
                  "paymentTypeId" => $ace_payment_method,
                  "packages" =>  $packages
                
              )
              ] 

        );
        
        $url = "<url here>";
        $api_key = "";
		
        $response = wp_remote_post( $url, 
				array(
					'headers'   => array('Authorization' => 'Basic ' . $api_key,
					    'Content-Type' => 'application/json; charset=utf-8'),
					'method'    => 'POST',
					'timeout' => 75,				    
					'body'	=> json_encode($pay_load)
				)
			);

            //echo "APi responded -----------------------=-----------------\n";
			$vars = json_decode($response['body'],true);
        	//print_r(json_encode($vars));

            //echo "end Api response ---------------------------------------------\n";

            // print_r($order);


            
            
 }


 ?>