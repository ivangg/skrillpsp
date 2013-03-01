jQuery(document).ready( function() {
    jQuery('#skrillcurrencychannels').hide();
    
    jQuery.each(jQuery('.skrilldelchannel'), function (key, value) {
        jQuery(this).unbind("click");
	jQuery(this).click(function (e) {
	    e.preventDefault();
            var id = jQuery(this).attr("id");
            
            var matches = id.match(/skrilldelchannel_(.*)/i);
            if (!matches)
                return;
            
            if (jQuery(this).text().match(/hide/i))
                {
                jQuery('#skrillchannelconfig_' + matches[1]).hide();
                jQuery('#skrillchannelconfigdiv_' + matches[1]).css('height','30px');
                jQuery(this).text('Show Channel/Currency Configuration');
                }
            else
                {
                jQuery('#skrillchannelconfig_' + matches[1]).show();
                jQuery('#skrillchannelconfigdiv_' + matches[1]).css('height','210px');
                jQuery(this).text('Hide Channel/Currency Configuration');
                }
        });
    });
    
    jQuery.each(jQuery('.skrillshowcountries'), function (key, value) {
        jQuery(this).unbind("click");
	jQuery(this).click(function (e) {
	    e.preventDefault();
            var id = jQuery(this).attr("id");
            
            var matches = id.match(/skrillshowcountries_(.*)/i);
            if (!matches)
                return;
            
            if (jQuery(this).text().match(/all countries/i))
                {
                jQuery('#skrillpaymentmethodcountry_' + matches[1]).hide();
                jQuery('#skrillpaymentmethoddiv_' + matches[1]).css('height','50px');
                jQuery(this).text('Restrain by Countries');
                }
            else
                {
                jQuery('#skrillpaymentmethodcountry_' + matches[1]).show();
                jQuery('#skrillpaymentmethoddiv_' + matches[1]).css('height','270px');
                jQuery(this).text('All Countries');
                }
        });
	
	var id = jQuery(this).attr("id");
	var matches = id.match(/skrillshowcountries_(.*)/i);
	if (!matches)
	    return;
	jQuery('#skrillpaymentmethodcountry_' + matches[1]).hide();
	jQuery('#skrillpaymentmethoddiv_' + matches[1]).css('height','50px');
    });
});