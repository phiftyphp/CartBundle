// Generated by CoffeeScript 1.6.3
(function() {
  $(function() {
    var $discountedAmount, $totalAmount;
    $totalAmount = $(".total-amount");
    $discountedAmount = $(".discounted-amount");
    $(".quantity-selector").change(function() {
      var $amount, $el, $row, id, quantity;
      $el = $(this);
      $row = $el.parents("tr");
      $amount = $row.find(".amount");
      id = $el.data("id");
      quantity = $el.val();
      return runAction("CartBundle::Action::UpdateCartItem", {
        id: id,
        quantity: quantity
      }, function(resp) {
        $.jGrowl(resp.message);
        if (resp.success) {
          $amount.text("$ " + resp.data.amount);
          $totalAmount.text("$ " + resp.data.total_amount);
          return $discountedAmount.text("$ " + resp.data.discounted_total_amount);
        }
      });
    });
    $(".coupon-submit-btn").click(function() {
      var couponCode;
      couponCode = $(".coupon-code").val();
      return runAction("CouponBundle::Action::ApplyCoupon", {
        coupon_code: couponCode
      }, function(resp) {
        if (resp.success) {
          $.jGrowl(resp.message);
          $(".discounted-summary").removeClass("hide");
          $(".total-amount").text("$ " + resp.data.total_amount).parent().addClass("line-through");
          return $(".discounted-amount").text("NT$ " + resp.data.discounted_total_amount);
        } else {
          $(".discounted-summary").addClass("hide");
          $(".total-amount").parent().removeClass("line-through");
          return $.jGrowl(resp.message, {
            theme: "error"
          });
        }
      });
    });
    return $(".remove-cart-item").click(function() {
      var $el, itemId;
      $el = $(this);
      itemId = $(this).data("id");
      return runAction("CartBundle::Action::RemoveCartItem", {
        id: itemId
      }, function(resp) {
        if (resp.success) {
          $.jGrowl(resp.message);
          $el.parents("tr").fadeOut();
          $totalAmount.text("$ " + resp.data.total_amount);
          return $discountedAmount.text("$ " + resp.data.discounted_total_amount);
        }
      });
    });
  });

  /*
  updateQuantitySelector = (typeId) ->
    $quantitySel = $(".quantity").empty()
    return  unless typeId
    ProductAPI.getType typeId, (productType) ->
      quantity = parseInt(productType.quantity)
      if quantity > 9
        quantity = 9
      else quantity = 9  if quantity is -1
      i = 1
  
      while i <= quantity
        $("<option/>").text(i).val(i).appendTo $quantitySel
        i++
  
  # when product type changed, we should also update the quantity
  $(".product-type").change ->
    updateQuantitySelector $(this).val()
  
  updateQuantitySelector $(".product-type").val()
  */


}).call(this);
