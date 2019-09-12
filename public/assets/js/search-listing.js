(function ($) {
    $('#at_biz_dir-location').select2({
        placeholder: atbdp_search_listing.i18n_text.location_selection,
        allowClear: false,
        templateResult: function (data) {
            // We only really care if there is an element to pull classes from
            if (!data.element) {
                return data.text;
            }

            var $element = $(data.element);

            var $wrapper = $('<span></span>');
            $wrapper.addClass($element[0].className);

            $wrapper.text(data.text);

            return $wrapper;
        }
    });

    // Category
    $('#at_biz_dir-category').select2({
        placeholder: atbdp_search_listing.i18n_text.category_selection,
        allowClear: false,
        templateResult: function (data) {
            // We only really care if there is an element to pull classes from
            if (!data.element) {
                return data.text;
            }

            var $element = $(data.element);

            var $wrapper = $('<span></span>');
            $wrapper.addClass($element[0].className);

            $wrapper.text(data.text);

            return $wrapper;
        }
    });

    //ad search js
    var showMore = atbdp_search_listing.i18n_text.show_more;
    var showLess = atbdp_search_listing.i18n_text.show_less;
    var checkbox = $(".bads-tags .custom-control");
    checkbox.slice(4).hide();
    var show_more = $(".more-less");
    show_more.on("click", function (e) {
        e.preventDefault();
        var txt = checkbox.slice(4).is(":visible") ? showMore : showLess;
        $(this).text(txt);
        checkbox.slice(4).slideToggle(200);
        $(this).toggleClass("ad");
    });
    if (checkbox.length <= 4) {
        show_more.remove();
    }


    var item = $('.custom-control').closest('.bads-custom-checks');
    item.each(function (index, el) {
        var count = 0;
        var abc = $(el)[0];
        var abc2 = $(abc).children('.custom-control');
        if(abc2.length <= 4){
            $(abc2).closest('.bads-custom-checks').next('a.more-or-less').hide();
        }
        $(abc2).slice(4, abc2.length).hide();

    });


    $('body').on('click', '.more-or-less', function(event) {
        event.preventDefault();
        var item = $(this).closest('.atbdp_cf_checkbox, .ads-filter-tags');
        var abc2 = $(item).find('.custom-control');
        $(abc2).slice(4, abc2.length).slideUp();

        $(this).toggleClass('active');

       if($(this).hasClass('active')){
           $(this).text(atbdp_search_listing.i18n_text.show_less);
           $(abc2).slice(4, abc2.length).slideDown();
       } else {
           $(this).text(atbdp_search_listing.i18n_text.show_more);
           $(abc2).slice(4, abc2.length).slideUp();
       }

    });


    $(".bads-custom-checks").parent(".form-group").addClass("ads-filter-tags");

    var ad = $(".ads_float .ads-advanced");
    ad.css({
        visibility: 'hidden',
        height: '0',
    });
    var count = 0;
    $("body").on("click", '.more-filter', function (e) {

        count++;
        e.preventDefault();
        var currentPos = e.clientY, displayPos = window.innerHeight, height = displayPos - currentPos;

        if (count % 2 === 0) {
            $(this).closest('.atbd_wrapper').find('.ads_float').find('.ads-advanced').css({
                visibility: 'hidden',
                opacity: '0',
                height: '0',
                transition: '.3s ease'
            });
        } else {
            $(this).closest('.atbd_wrapper').find('.ads_float').find('.ads-advanced').css({
                visibility: 'visible',
                height: height - 70 + 'px',
                transition: '0.3s ease',
                opacity: '1',
            });
        }
    });

    var ad_slide = $(".ads_slide .ads-advanced");
    ad_slide.hide().slideUp();
    $(".more-filter").on("click", function (e) {
        e.preventDefault();
        $(this).closest('.atbd_wrapper').find('.ads_slide').find('.ads-advanced').slideToggle().show();
        $(".ads_slide .ads-advanced").toggleClass("ads_ov")
    });
    $(".ads-advanced").parents("div").css("overflow", "visible");

    //remove preload after window load
    $(window).load(function () {
        $("body").removeClass("atbdp_preload");
        $('.button.wp-color-result').attr('style', ' ');
    });

    // Price Range Slider
    var slider_range = $(".atbd_slider-range");
    var miles = atbdp_search_listing.i18n_text.Miles;
    slider_range.each(function () {
        $(this).slider({
            range: "min",
            min: 0,
            max: 1000,
            value: 0,
            slide: function (event, ui) {
                $(".atbdpr_amount").text(ui.value + miles);
                $("#atbd_rs_value").val(ui.value);
            }
        });
    });
    $(".atbdpr_amount").text(slider_range.slider("value") + miles);
    $("#atbd_rs_value").val(slider_range.slider("value"));

    if(atbdp_search_listing.i18n_text.select_listing_map === 'google') {
        function initialize() {
            var input = document.getElementById('address');
            var autocomplete = new google.maps.places.Autocomplete(input);
            google.maps.event.addListener(autocomplete, 'place_changed', function () {
                var place = autocomplete.getPlace();
                document.getElementById('cityLat').value = place.geometry.location.lat();
                document.getElementById('cityLng').value = place.geometry.location.lng();
            });
        }
        google.maps.event.addDomListener(window, 'load', initialize);
    }else if(atbdp_search_listing.i18n_text.select_listing_map === 'openstreet') {
        $('#address').on('keyup', function(event) {
            event.preventDefault();
            var search = $('#address').val();
            $('#address_result').css({'display':'block'});
            if(search === ""){
                $('#result').css({'display':'none'});
            }
            var res = "";
            $.ajax({
                url: `http://nominatim.openstreetmap.org/?q=%27+${search}+%27&format=json`,
                type: 'POST',
                data: {},
                success: function (data) {
                    //console.log(data);
                    for (var i = 0; i < data.length; i++) {
                        res += `<li><a href="#" data-lat=${data[i].lat} data-lon=${data[i].lon}>${data[i].display_name}</a></li>`
                    }
                    $('#address_result ul').html(res);
                }
            });
        });

        $('body').on('click', '#address_result ul li a', function(event) {
            event.preventDefault();
            let text = $(this).text(),
                lat = $(this).data('lat'),
                lon = $(this).data('lon');

            $('#cityLat').val(lat);
            $('#cityLng').val(lon);

            $('#address').val(text);
            $('#address_result').hide();
        });
    }
    
})(jQuery);