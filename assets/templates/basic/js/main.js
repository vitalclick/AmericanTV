(function ($) {
  "use strict";

  // ============== Variables Start ======
  var $short_slider = $(".short_slider"),
    $short_slider_wh = $(".short_slider_wh"),
    $tag_sliders = $(".tag_sliders");
  // $short_play_sliders = $('.short_play_sliders');
  // ============== Variables End ========

  // ============== Header Hide Click On Body Js Start ========
  $(".header-button").on("click", function () {
    $(".body-overlay").toggleClass("show");
  });
  $(".body-overlay").on("click", function () {
    $(".header-button").trigger("click");
    $(this).removeClass("show");
  });
  // =============== Header Hide Click On Body Js End =========

  // Sidebar Dropdown Menu Start
  $(".has-dropdown > a").on("click", function (e) {
    e.preventDefault(); // Prevent default anchor behavior

    var submenu = $(this).next(".sidebar-submenu");

    // If the submenu is visible, slide it up and hide it
    if (submenu.is(":visible")) {
      submenu.slideUp(300, function () {
        submenu.removeClass("d-block").addClass("d-none"); // Hide after sliding up
      });
    } else {
      // Close all other submenus first
      $(".sidebar-submenu")
        .filter(":visible")
        .slideUp(300, function () {
          $(this).removeClass("d-block").addClass("d-none"); // Ensure others are hidden
        });

      // Remove 'd-none' before sliding down to allow animation
      submenu.removeClass("d-none").slideDown(300, function () {
        submenu.addClass("d-block"); // Show after sliding down
      });
    }
  });
  // Sidebar Dropdown Menu End

  /*======= popup container js start here =======*/
  $(".micBtn").click(function () {
    $(".popup-container").addClass("show");
    $(".body-overlay").addClass("show-overlay");
  });

  $(".close-icon, .body-overlay").click(function () {
    $(".popup-container").removeClass("show");
    $(".body-overlay").removeClass("show-overlay");
  });

  // =========================================================================================================
  //    Document Ready function Start
  // =========================================================================================================

  $(document).ready(function () {
    // ========================== Header Hide Scroll Bar Js Start =====================
    $(".navbar-toggler.header-button").on("click", function () {
      $("body").toggleClass("scroll-hide");
    });
    $(".body-overlay").on("click", function () {
      $("body").removeClass("scroll-hide");
    });
    // ========================== Header Hide Scroll Bar Js End =====================

    // ========================== Toggle Search Box Js Start =====================
    $(".toggle-search").on("click", function () {
      $(".toggle-search__box").addClass("show");
      $("body").addClass("scroll-hide");
    });
    $(".toggle-search__close").on("click", function () {
      $(".toggle-search__box").removeClass("show");
      $("body").removeClass("scroll-hide");
    });
    // ========================== Toggle Search Box Js End =====================

    // ================== Password Show Hide Js Start ==========
    $(".toggle-password").on("click", function () {
      $(this).toggleClass(" fa-eye-slash");
      var input = $($(this).attr("id"));
      if (input.attr("type") == "password") {
        input.attr("type", "text");
      } else {
        input.attr("type", "password");
      }
    });
    // =============== Password Show Hide Js End =================

    // ========================== Add Attribute For Bg Image Js Start =====================
    $(".bg-img").css("background", function () {
      var bg = "url(" + $(this).data("background-image") + ")";
      return bg;
    });
    // ========================== Add Attribute For Bg Image Js End =====================

    // ===================== Table Delete Column Js Start =================
    $(".delete-icon").on("click", function () {
      $(this).closest("tr").addClass("d-none");
    });
    // ===================== Table Delete Column Js End =================

    // ========================= Owl Carousel Js Start ===================

    // Tag Slider
    if ($tag_sliders.length > 0) {
      var $tag_sliders_obj = $tag_sliders.owlCarousel({
        autoplay: false,
        margin: 6,
        loop: false,
        dots: false,
        nav: true,
        navText: [
          '<i class="las la-angle-left"></i>',
          '<i class="las la-angle-right"></i>',
        ],
        autoWidth: true,
        responsiveClass: true,
        responsive: {
          0: {},
        },
      });
    }

    // Short Slider For Home Page
    if ($short_slider.length > 0) {
      var $short_slider_obj = $short_slider.owlCarousel({
        autoplay: false,
        margin: 24,
        loop: false,
        dots: false,
        nav: false,
        responsiveClass: true,
        responsive: {
          0: {
            items: 1,
            margin: 10,
          },
          400: {
            items: 2,
            margin: 15,
          },
          576: {
            items: 3,
            margin: 15,
          },
          600: {
            items: 3,
            margin: 24,
          },
          992: {
            items: 4,
          },
          1200: {
            items: 5,
          },
          1600: {
            items: 6,
          },
        },
      });
    }

    // Short Slider For Watch History Page
    if ($short_slider_wh.length > 0) {
      var $short_slider_wh_obj = $short_slider_wh.owlCarousel({
        autoplay: false,
        margin: 24,
        loop: false,
        dots: false,
        nav: true,
        navText: [
          '<span><i class="las la-angle-left"></i></span>',
          '<span><i class="las la-angle-right"></i></span>',
        ],
        responsiveClass: true,
        responsive: {
          0: {
            items: 1,
            margin: 10,
          },
          400: {
            items: 2,
            margin: 15,
          },
          576: {
            items: 3,
            margin: 15,
          },
          600: {
            items: 3,
            margin: 24,
          },
          992: {
            items: 4,
          },
          1200: {
            items: 5,
          },
          1700: {
            items: 6,
          },
        },
      });
    }

    // ========================= Owl Carousel Js End ===================

    // ============================ToolTip Js Start=====================
    const tooltipTriggerList = document.querySelectorAll(
      '[data-bs-toggle="tooltip"]'
    );
    const tooltipList = [...tooltipTriggerList].map(
      (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
    );
    // ============================ToolTip Js End========================

    // ========================= Menu Handaling Js Start ==========

    // Menu Handaling Main
    $(".menu-button").on("click", function () {
      $(".sidebar-menu").toggleClass("show-sm");
      $(".home__right").toggleClass("big-space");
      $(this).toggleClass("active");
    });
    // ========================= Menu Handaling Js End ==========

    // ==================== User Profile Dropdown Start ==================

    $(".user-info__button").on("click", function () {
      $(".user-info-list").toggleClass("show");
    });

    $(document).on("click", function (event) {
      var target = $(event.target);

      if (
        !target.closest(".user-info__button").length &&
        !target.closest(".user-info-list").length
      ) {
        $(".user-info-list").removeClass("show");
      }
    });

    // User Profile Inner Dropdown
    $(".has-dropdown > a").click(function () {
      $(".user-info-submenu").slideUp(200);
      if ($(this).parent().hasClass("active")) {
        $(".has-dropdown").removeClass("active");
        $(this).parent().removeClass("active");
      } else {
        $(".has-dropdown").removeClass("active");
        $(this).next(".user-info-submenu").slideDown(200);
        $(this).parent().addClass("active");
      }
    });

    // ==================== User Profile Dropdown End ==================

    // ==================== User Profile Dropdown Start ==================
    $(".manageCreate").on("click", function () {
      $(".create__list").toggleClass("show");
    });

    $(document).on("click", function (event) {
      var target = $(event.target);

      if (
        !target.closest(".manageCreate").length &&
        !target.closest(".create__list").length
      ) {
        $(".create__list").removeClass("show");
      }
    });

    // ==================== User Profile Dropdown End ==================

    // ==================== Notification Dropdown Start ==================
    $(".notification__btn").on("click", function () {
      $(".notification__list").toggleClass("show");
    });

    $(document).on("click", function (event) {
      var target = $(event.target);

      if (
        !target.closest(".notification__btn").length &&
        !target.closest(".notification__list").length
      ) {
        $(".notification__list").removeClass("show");
      }
    });
    // ==================== Notification Dropdown End ==================

    // ========================= Small Device Search And Little Bit Header Item Js Start =====================
    $(".sm-search-btn").on("click", function () {
      $(".search-close").toggleClass("show");
      $(".search-form").toggleClass("show");
      $(".body-overlay").toggleClass("show");
    });
    $(".body-overlay, .search-close").on("click", function () {
      $(".body-overlay").removeClass("show");
      $(".search-close").removeClass("show");
      $(".search-form").removeClass("show");
    });
    // ========================= Small Device Search And Little Bit Header Item Js End =====================

    // ========================== Progressbar Js Start ===================

    // Video Uploading Progressbar
    startAnimation();

    function startAnimation() {
      jQuery(".uploading-progress__progress").each(function () {
        jQuery(this)
          .find(".uploading-progress__progressbar")
          .animate(
            {
              width: jQuery(this).attr("data-percent"),
            },
            6000
          );
      });
    }
    // ========================== Progressbar Js End =====================
  });
  // =========================================================================================================
  //    Document Ready function End
  // =========================================================================================================

  // ========================= Preloader Js Start =====================
  $(window).on("load", function () {
    $(".preloader").fadeOut();
  });

  document.addEventListener("DOMContentLoaded", () => {
    $("body").css("display", "block");
    let headerHeight = $(".home-header").height();
    document.documentElement.style.setProperty(
      "--header-h",
      headerHeight + "px"
    );
  });

  // ========================= Preloader Js End =====================

  // ========================= Header Sticky Js Start ==============
  $(window).on("scroll", function () {
    if ($(window).scrollTop() >= 300) {
      $(".header").addClass("fixed-header");
    } else {
      $(".header").removeClass("fixed-header");
    }
  });
  // ========================= Header Sticky Js End ===================

  //============================ Scroll To Top Icon Js Start =========
  var btn = $(".scroll-top");

  $(window).scroll(function () {
    if ($(window).scrollTop() > 300) {
      btn.addClass("show");
    } else {
      btn.removeClass("show");
    }
  });

  btn.on("click", function (e) {
    e.preventDefault();
    $("html, body").animate(
      {
        scrollTop: 0,
      },
      "300"
    );
  });
  //========================= Scroll To Top Icon Js End ======================

  //========================= Channel Search Js Start ======================

  // For Form Input
  $(".channel-search-btn").on("click", function () {
    $(".channel-tab .channel-search .form-group .form--control").toggleClass(
      "show"
    );
  });

  $(document).on("click", function (event) {
    var target = $(event.target);

    if (
      !target.closest(".channel-search-btn").length &&
      !target.closest(".channel-tab .channel-search .form-group .form--control")
        .length
    ) {
      $(".channel-tab .channel-search .form-group .form--control").removeClass(
        "show"
      );
    }
  });

  // For Channel Tab Hidden
  $(".channel-search-btn").on("click", function () {
    $(".channel-tab__item").toggleClass("hide");
  });

  $(document).on("click", function (event) {
    var target = $(event.target);

    if (
      !target.closest(".channel-search-btn").length &&
      !target.closest(".channel-tab__item").length
    ) {
      $(".channel-tab__item").removeClass("hide");
    }
  });

  //========================= Channel Search Js End ======================

  // ========================== Watch History Search Responsive Class Js Start =====================
  // For Channel Tab Hidden
  $(".wh-sm-search").on("click", function () {
    $(this).toggleClass("change-icon");
    $(".watch-history-search").toggleClass("show");
  });

  $(document).on("click", function (event) {
    var target = $(event.target);

    if (
      !target.closest(".wh-sm-search").length &&
      !target.closest(".watch-history-search").length
    ) {
      $(".watch-history-search").removeClass("show");
      $(".wh-sm-search").removeClass("change-icon");
    }
  });
  // ========================== Watch History Search Responsive Class Js End =====================

  // ========================== Ellipsis Button And List Js Start =====================
  $(".ellipsis-btn").on("click", function () {
    $(this)
      .parent(".ellipsis-wrapper")
      .find(".ellipsis-list")
      .toggleClass("show");
  });

  $(document).on("click", function (event) {
    var target = $(event.target);

    if (
      !target.closest(".ellipsis-btn").length &&
      !target.closest(".ellipsis-list").length
    ) {
      $(".ellipsis-list").removeClass("show");
    }
  });
  // ========================== Ellipsis Button And List Js End =====================

  // ========================== File Upload With Drag And Drop Js Start =====================

  function dragNdrop(event) {
    var fileName = URL.createObjectURL(event.target.files[0]);
    var preview = document.getElementById("preview");
    var previewImg = document.createElement("img");
    previewImg.setAttribute("src", fileName);
    preview.innerHTML = "";
    preview.appendChild(previewImg);
  }

  // ========================== File Upload With Drag And Drop Js End =====================

  // ========================== Setting Menu Js End =====================
  $(".setting-menu-btn").on("click", function () {
    $(".setting-menu").addClass("show");
    $(".sidebar-overlay").addClass("show");
  });
  $(".setting-menu__close, .sidebar-overlay").on("click", function () {
    $(".setting-menu").removeClass("show");
    $(".sidebar-overlay").removeClass("show");
  });
  // ========================== Setting Menu Js End =====================

  // ========================== Country Code Custom Dropdown Js Start ===================
  $(".country_code > .country_code__caption").on("click", function () {
    $(this).parent().toggleClass("open");
  });

  $(".country_code > .country_code__list > .country_code__item").on(
    "click",
    function () {
      $(
        ".country_code > .country_code__list > .country_code__item"
      ).removeClass("selected");
      $(this)
        .addClass("selected")
        .parent()
        .parent()
        .removeClass("open")
        .children(".country_code__caption")
        .html($(this).html());
      
    }
  );

  $(document).on("keyup", function (evt) {
    if ((evt.keyCode || evt.which) === 27) {
      $(".country_code").removeClass("open");
    }
  });

  $(document).on("click", function (evt) {
    if (
      $(evt.target).closest(".country_code > .country_code__caption").length ===
      0
    ) {
      $(".country_code").removeClass("open");
    }
  });

  // ========================== Country Code Custom Dropdown Js End =====================

  // ========================== Setting Menu Js End =====================
  $(".dashboard-menu-btn").on("click", function () {
    $(".dashboard-menu").addClass("show");
    $(".sidebar-overlay").addClass("show");
  });
  $(".dashboard-menu__close, .sidebar-overlay").on("click", function () {
    $(".dashboard-menu").removeClass("show");
    $(".sidebar-overlay").removeClass("show");
  });
  // ========================== Setting Menu Js End =====================

  // ========================== Withdraw Method Custom Dropdown Js Start =====================
  $(".withdraw-method > .withdraw-method__selected").on("click", function () {
    $(this).parent().toggleClass("open");
  });

  $(".withdraw-method > .withdraw-method__list > .withdraw-method__item").on(
    "click",
    function () {
      $(
        ".withdraw-method > .withdraw-method__list > .withdraw-method__item"
      ).removeClass("selected");
      $(this)
        .addClass("selected")
        .parent()
        .parent()
        .removeClass("open")
        .children(".withdraw-method__selected")
        .html($(this).html());
    }
  );

  $(document).on("keyup", function (evt) {
    if ((evt.keyCode || evt.which) === 27) {
      $(".withdraw-method").removeClass("open");
    }
  });

  $(document).on("click", function (evt) {
    if (
      $(evt.target).closest(".withdraw-method > .withdraw-method__selected")
        .length === 0
    ) {
      $(".withdraw-method").removeClass("open");
    }
  });

  $(".custom--dropdown > .custom--dropdown__selected").on("click", function () {
    $(this).parent().toggleClass("open");
  });

  $(".custom--dropdown > .dropdown-list > .dropdown-list__item").on(
    "click",
    function () {
      $(
        ".custom--dropdown > .dropdown-list > .dropdown-list__item"
      ).removeClass("selected");
      $(this)
        .addClass("selected")
        .parent()
        .parent()
        .removeClass("open")
        .children(".custom--dropdown__selected")
        .html($(this).html());
    }
  );

  $(document).on("keyup", function (evt) {
    if ((evt.keyCode || evt.which) === 27) {
      $(".custom--dropdown").removeClass("open");
    }
  });

  $(document).on("click", function (evt) {
    if (
      $(evt.target).closest(".custom--dropdown > .custom--dropdown__selected")
        .length === 0
    ) {
      $(".custom--dropdown").removeClass("open");
    }
  });
  // ========================== Withdraw Method Custom Dropdown Js End =====================

  // image uploader
  $(".upload-image__btn").on("change", function (event) {
    const file = event.target.files[0];
    const parent = $(this).closest(".upload-image");

    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = (e) =>
        parent.find(".upload-image__thumb img").attr("src", e.target.result);
      reader.readAsDataURL(file);
    }
  });



     // ==================== Theme Switch Button Start ==================
     const toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
     const currentTheme = localStorage.getItem('theme');

     if (currentTheme) {
         document.documentElement.setAttribute('data-theme', currentTheme);

         if (currentTheme === 'light') {
             toggleSwitch.checked = true;
         }
     }

     function switchTheme(e) {
         if (e.target.checked) {
             document.documentElement.setAttribute('data-theme', 'light');
             localStorage.setItem('theme', 'light');
         } else {
             document.documentElement.setAttribute('data-theme', 'dark');
             localStorage.setItem('theme', 'dark');
         }
     }

     toggleSwitch.addEventListener('change', switchTheme, false);

     // ==================== Theme Switch Button End ==================

     
})(jQuery);
