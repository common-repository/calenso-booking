if (window.jQuery) {
    //console.log("jquery admin");
}

//Gruppentermin zeigen falls ausgewählt
jQuery(document).on('change', '.zpt-booking-type', function (e) {

    //console.log("'change', '.zpt-booking-type'");

    switch (jQuery(this).val()) {
        case 'appointment':
            jQuery('.zpt-event-data').addClass('zpt-hidden');
            jQuery('.dienstleistung').removeClass('zpt-hidden');
            jQuery('.filiale').removeClass('zpt-hidden');
            jQuery('.mitarbeiter').removeClass('zpt-hidden');
            break;
        case 'event':
            jQuery('.zpt-event-data').removeClass('zpt-hidden');
            jQuery('.dienstleistung').addClass('zpt-hidden');
            jQuery('.filiale').addClass('zpt-hidden');
            jQuery('.mitarbeiter').addClass('zpt-hidden');
            break;
    }
});