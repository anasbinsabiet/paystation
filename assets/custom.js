jQuery(document).ready(function ($) {

  $(document.body).on("click", "button#track-order-button", function () {
    const cartTotal = document.getElementById("cartTotal").value;

    let errorElement = "";
    const payment_method = $('input[name="payment_method"]:checked').val();
    const baseurl = document.getElementById("baseurl").value;
    const payment_url = document.getElementById("payment_url").value;

    const billing_first_name = document.getElementById("billing_first_name")?.value || 'N/A';
    const billing_last_name = document.getElementById("billing_last_name")?.value || 'N/A';
    const billing_company = document.getElementById("billing_company")?.value || 'N/A';
    const billing_email = document.getElementById("billing_email").value;
    const billing_phone = document.getElementById("billing_phone").value;
    const billing_address_1 = document.getElementById("billing_address_1")?.value || 'N/A';
    const billing_address_2 = document.getElementById("billing_address_2")?.value || 'N/A';
    const billing_city = document.getElementById("billing_city")?.value || 'N/A';
    const billing_state = document.getElementById("billing_state")?.value || 'N/A';
    const billing_postcode = document.getElementById("billing_postcode")?.value || 'N/A';
    const billing_country = document.getElementById("billing_country")?.value || 'N/A';

    // Shipping address fields
    const shipping_first_name = document.getElementById("shipping_first_name")?.value || 'N/A';
    const shipping_last_name = document.getElementById("shipping_last_name")?.value || 'N/A';
    const shipping_company = document.getElementById("shipping_company")?.value || 'N/A';
    const shipping_address_1 = document.getElementById("shipping_address_1")?.value || 'N/A';
    const shipping_address_2 = document.getElementById("shipping_address_2")?.value || 'N/A';
    const shipping_city = document.getElementById("shipping_city")?.value || 'N/A';
    const shipping_state = document.getElementById("shipping_state")?.value || 'N/A';
    const shipping_postcode = document.getElementById("shipping_postcode")?.value || 'N/A';
    const shipping_country = document.getElementById("shipping_country")?.value || 'N/A';

    const url = baseurl + "/wp-admin/admin-ajax.php";
    const ps_merchant_id = document.getElementById("ps_merchant_id").value;
    const ps_password = document.getElementById("ps_password").value;

    if (cartTotal > 0) {


      // $.post(url, { action: "get_cart_items" }, function (response) {
      //   if (response.length) {
      //     apiCall(response);
      //   }
      // });

      const demo = $(".checkout").serializeArray();

      $.post(url, { action: "complete_order", data: demo }, function (response) {
        if (response.order_id > 0 && payment_method === 'paystation_payment_gateway' && response.returnURL) {
          makePayment(response.order_id, response.returnURL);
        } else if (response.order_id > 0 && payment_method === 'cod') {
          window.open(response.returnURL, "_self");
        } else {
          const message = "Failed to complete order";
          errorElement =
            "<br /><span style='color: #a94442;background-color: #f2dede;border-color: #ebccd1;padding: 15px;border: 1px solid transparent;border-radius: 4px;'>" +
            message +
            "</span>";
          $("#payment").append(errorElement);
        }
      });
      function makePayment(order_id, returnURL) {
        const obj = {
          merchantId: ps_merchant_id,
          password: ps_password,
        };
        const body = {
          access: obj,
          cartTotal: cartTotal,
          billing_first_name: billing_first_name,
          billing_last_name: billing_last_name,
          billing_company: billing_company,
          billing_email: billing_email,
          billing_phone: billing_phone,
          billing_address_1: billing_address_1,
          billing_address_2: billing_address_2,
          billing_city: billing_city,
          billing_state: billing_state,
          billing_postcode: billing_postcode,
          billing_country: billing_country,
          shipping_first_name: shipping_first_name,
          shipping_last_name: shipping_last_name,
          shipping_company: shipping_company,
          shipping_address_1: shipping_address_1,
          shipping_address_2: shipping_address_2,
          shipping_city: shipping_city,
          shipping_state: shipping_state,
          shipping_postcode: shipping_postcode,
          shipping_country: shipping_country,
          invoice_number: order_id,
          baseurl: baseurl,
          returnURL: returnURL,
        };
        if (cartTotal > 0) {
          $.ajax({
            url: payment_url,
            data: body,
            method: "POST",
            dataType: "json",
            success: function (data) {
              if (data.status === "success") {
                window.open(data.payment_url, "_self");
              } else {
                $("button#track-order-button").attr("disabled", true);
                errorElement =
                  "<br /><span style='color: #a94442;background-color: #f2dede;border-color: #ebccd1;padding: 15px;border: 1px solid transparent;border-radius: 4px;'>" +
                  data.status +
                  " - " +
                  data.message +
                  "</span>";
                $("#payment").append(errorElement);
              }
            },
          });
        }
      }
    } else {
      const message = "Order amount must be greater than 0!";
      errorElement =
        "<br /><span style='color: #a94442;background-color: #f2dede;border-color: #ebccd1;padding: 15px;border: 1px solid transparent;border-radius: 4px;'>" +
        message +
        "</span>";
      $("#payment").append(errorElement);
    }
  });

  // $(document.body).on("change", "input[name=payment_method]", function () {
  //   if (this.value == "paystation_payment_gateway") {
  //     $("#place_order").hide();
  //     $("#track-order-button").show();
  //   } else {
  //     $("#place_order").show();
  //     $("#track-order-button").hide();
  //   }
  // });

  const wc_payment_status = document.getElementById("wc_payment_status")?.value;
  if (wc_payment_status && wc_payment_status === "cancelled") {
    $('h1.wp-block-woocommerce-legacy-template').text('Order Cancelled!');
    $('.woocommerce-order-received .woocommerce-thankyou-order-received').text('Your order has been cancelled.');
    $('.woocommerce-order-details').hide();
    $('.woocommerce-customer-details').hide();
  }
});
