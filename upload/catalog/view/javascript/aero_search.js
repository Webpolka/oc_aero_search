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
      // Если сервер вернул редирект (товар с опциями)
      if (data.redirect) {
        window.location.href = data.redirect;
        return; // прекращаем выполнение функции
      }

      // Если успех — уведомление и обновление корзины
      if (data.success) {
        lsNotify("success", data.success);
        updateHeaderCart();
      }

      // Ошибка (не редирект)
      if (data.error) {
        lsNotify("error", data.error);
      }
    })
    .catch((err) => {
      console.error("Error in cartAdd:", err);
      lsNotify("error", "An unexpected error occurred.");
    });
}

// Update cart
function updateHeaderCart() {
  fetch("index.php?route=common/cart.info", {
    method: "GET",
    headers: { "X-Requested-With": "XMLHttpRequest" },
  })
    .then((res) => res.text())
    .then((html) => {
      // создаём временный контейнер
      const tempDiv = document.createElement("div");
      tempDiv.innerHTML = html;

      // вытаскиваем полный блок корзины из AJAX
      const newCart = tempDiv.querySelector(".dropdown.d-grid");
      const currentCart = document.querySelector("#cart");
      const correntCartGrid = currentCart.querySelector(".dropdown.d-grid");

      if (newCart && currentCart) {
        correntCartGrid.innerHTML = newCart.innerHTML;
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

// Render review stars
function renderAeroRating(rating) {
  const StarFilled = `
<svg viewBox="0 0 1024 1024" class="star star-filled" width="15" height="15">
  <path fill="orange" d="m908.1 353.1l-253.9-36.9L540.7 86.1c-3.1-6.3-8.2-11.4-14.5-14.5c-15.8-7.8-35-1.3-42.9 14.5L369.8 316.2l-253.9 36.9c-7 1-13.4 4.3-18.3 9.3a32.05 32.05 0 0 0 .6 45.3l183.7 179.1l-43.4 252.9a31.95 31.95 0 0 0 46.4 33.7L512 754l227.1 119.4c6.2 3.3 13.4 4.4 20.3 3.2c17.4-3 29.1-19.5 26.1-36.9l-43.4-252.9l183.7-179.1c5-4.9 8.3-11.3 9.3-18.3c2.7-17.5-9.5-33.7-27-36.3"/>
</svg>
`;
  const StarEmpty = `
<svg viewBox="0 0 1024 1024" class="star star-empty" width="15" height="15">
  <path fill="orange" d="m908.1 353.1l-253.9-36.9L540.7 86.1c-3.1-6.3-8.2-11.4-14.5-14.5c-15.8-7.8-35-1.3-42.9 14.5L369.8 316.2l-253.9 36.9c-7 1-13.4 4.3-18.3 9.3a32.05 32.05 0 0 0 .6 45.3l183.7 179.1l-43.4 252.9a31.95 31.95 0 0 0 46.4 33.7L512 754l227.1 119.4c6.2 3.3 13.4 4.4 20.3 3.2c17.4-3 29.1-19.5 26.1-36.9l-43.4-252.9l183.7-179.1c5-4.9 8.3-11.3 9.3-18.3c2.7-17.5-9.5-33.7-27-36.3M664.8 561.6l36.1 210.3L512 672.7L323.1 772l36.1-210.3l-152.8-149L417.6 382L512 190.7L606.4 382l211.2 30.7z"/>
</svg>
`;

  let html = "";

  for (let i = 1; i <= 5; i++) {
    html += i <= rating ? StarFilled : StarEmpty;
  }

  return html;
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


    // Normalize lang code
    function normalizeLanguageCode(input) {
      if (!input) return "en-gb";

      const map = {
        // 🇷🇺 Русский
        ru: "ru-ru",
        "ru-ru": "ru-ru",
        ru_ru: "ru-ru",
        russian: "ru-ru",

        // 🇬🇧 Английский
        en: "en-gb",
        "en-gb": "en-gb",
        en_us: "en-gb",
        "en-us": "en-gb",
        gb: "en-gb",
        english: "en-gb",

        // 🇫🇷 Французский
        fr: "fr-fr",
        "fr-fr": "fr-fr",
        fr_ca: "fr-fr",
        french: "fr-fr",
      };

      const code = String(input).toLowerCase().replace("_", "-");

      return map[code] || "en-gb";
    }
    
    const lang = normalizeLanguageCode(document.documentElement.lang);

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
              Math.abs(cat_id) +
              "&language=" + lang;
            all_search_href =
              all_search_href +
              encodeURIComponent(filter_name) +
              "&category_id=" +
              Math.abs(cat_id) +
              "&language=" + lang;
          } else {
            aero_search_href =
              aero_search_href +
              encodeURIComponent(filter_name) +
              "&language=" + lang;
            all_search_href =
              all_search_href +
              encodeURIComponent(filter_name) +
              "&language=" + lang;
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
                  html += '<div class="product-main">';

                  html += '<div class="product-main-top">';
                  html +=
                    '<span class="product-main-name">' +
                    product.name +
                    "</span>";
                  html +=
                    '<span class="product-main-stars">' +
                    renderAeroRating(product.rating) +
                    "</span>";
                  html += "</div>";

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
