// Card add
function cartAdd(product_id, quantity, imageSelector) {
  fetch("index.php?route=checkout/cart.add", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: `product_id=${product_id}&quantity=${quantity}`,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        lsNotify("success", data.success);

        //  обновляем кнопку корзины
        updateHeaderCart();
      }
      if (data.error) {
        lsNotify("error", data.error);
      }
    });
}


function updateHeaderCart() {
    fetch("index.php?route=common/cart/info", {
        method: "GET",
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(res => res.text())
    .then(html => {
        // создаём временный элемент, чтобы вытащить текст кнопки
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;

        // OpenCart стандартно отдаёт мини-корзину с кнопкой, ищем её
        const newBtn = tempDiv.querySelector('button.btn.dropdown-toggle');
        if (newBtn) {
            const headerBtn = document.querySelector('button.btn.dropdown-toggle');
            if (headerBtn) {
                headerBtn.innerHTML = newBtn.innerHTML;
            }
        }
    });
}


// Notife Lite function
function lsNotify(type, text, delay = 3000) {
  if (!$(".notify-lite-container").length) {
    $("body").append('<div class="notify-lite-container"></div>');
  }

  const toast = $('<div class="notify-lite ' + type + '">' + text + "</div>");
  $(".notify-lite-container").append(toast);

  // animation show
  setTimeout(() => toast.addClass("show"), 10);

  // hide after delay
  setTimeout(() => {
    toast.removeClass("show");
    setTimeout(() => toast.remove(), 300);
  }, delay);
}

// Aero search function
const AeroSearch = (function () {
  const init = function (options) {
    const aero_search = {
      selector: ".input-group input[name='search']",
      text_no_matches: options.text_empty,
      height: "50px",
    };

    // Craete dropdow list
    const html =
      '<div class="aero-search"><ul></ul><div class="result-text"></div></div>';
    $(aero_search.selector).after(html);

    $(aero_search.selector).autocomplete({
      source: function (request, response) {
        const filter_name = $(aero_search.selector).val();
        let cat_id = 0;
        const aero_search_min_length = options.module_aero_search_min_length;
        if (filter_name.length < aero_search_min_length) {
          $(".aero-search").css("display", "none");
        } else {
          let aero_search_href =
            "index.php?route=extension/aero_search/module/aero_search.search&filter_name=";
          let all_search_href = "index.php?route=product/search&search=";
          if (cat_id > 0) {
            aero_search_href =
              aero_search_href +
              encodeURIComponent(filter_name) +
              "&cat_id=" +
              Math.abs(cat_id);
            all_search_href =
              all_search_href +
              encodeURIComponent(filter_name) +
              "&category_id=" +
              Math.abs(cat_id);
          } else {
            aero_search_href =
              aero_search_href + encodeURIComponent(filter_name);
            all_search_href = all_search_href + encodeURIComponent(filter_name);
          }

          let html = "<li>";
          html +=
            '<img class="loading" src="extension/aero_search/catalog/view/media/loading.gif" />';
          html += "</li>";
          $(".aero-search ul").html(html);
          $(".aero-search").css("display", "block");

          $.ajax({
            url: aero_search_href,
            dataType: "json",
            success: function (result) {
              const products = result.products;

              console.log(result);

              $(".aero-search ul li").remove();
              $(".result-text").html("");
              if (!$.isEmptyObject(products)) {
                const show_image = options.module_aero_search_show_image;
                const show_price = options.module_aero_search_show_price;
                const show_description =
                  options.module_aero_search_show_description;
                const show_add_button =
                  options.module_aero_search_show_add_button;

                $(".result-text").html(
                  '<a href="' +
                    all_search_href +
                    '" class="view-all-results">' +
                    options.text_view_all_results +
                    " (" +
                    result.total +
                    ")</a>"
                );
                $.each(products, function (index, product) {
                  let html = "<li>";

                  html +=
                    '<a href="' +
                    product.url +
                    '" title="' +
                    product.name +
                    '">';
                  // show image
                  if (product.image && show_image == 1) {
                    html +=
                      '<div class="product-image"><img alt="' +
                      product.name +
                      '" src="' +
                      product.image +
                      '"></div>';
                  }
                  // show name & extra_info
                  html += '<div class="product-name">' + product.name;
                  if (show_description == 1) {
                    html += "<p>" + product.extra_info + "</p>";
                  }
                  html += "</div>";

                  html += "</a>";

                  html += '<div class="product-action-price">';

                  // show price & special price
                  if (show_price == 1) {
                    if (product.special) {
                      html +=
                        '<div class="product-price"><span class="special">' +
                        product.price +
                        '</span><span class="price">' +
                        product.special +
                        "</span></div>";
                    } else {
                      html +=
                        '<div class="product-price"><span class="price">' +
                        product.price +
                        "</span></div>";
                    }
                  }

                  // show Add button
                  if (show_add_button == 1) {
                    html += '<div class="product-add-cart">';
                    html +=
                      '<a href="javascript:;" onclick="cartAdd(' +
                      product.product_id +
                      ", " +
                      product.minimum +
                      ');" class="btn btn-primary">';
                    html += '<i class="fa fa-shopping-cart"></i>';
                    html += "</a>";
                    html += "</div>";
                  }

                  html += "</div>";

                  html += "</li>";
                  $(".aero-search ul").append(html);
                });
              } else {
                let html = "";
                html += '<li style="text-align: center;height:10px;">';
                html += aero_search.text_no_matches;
                html += "</li>";

                $(".aero-search ul").html(html);
              }
              
              $(".aero-search").css("display", "block");

              return false;
            },
          });
        }
      },
      select: function (product) {
        $(aero_search.selector).val(product.name);
      },
    });

    $(document).bind("mouseup touchend", function (e) {
      const container = $(".aero-search");
      if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.hide();
      }
    });
  };

  return {
    //Start
    start: function (options) {
      init(options);
    },
  };
})();
