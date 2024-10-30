if(!window.jQuery)
{
    // wait until jQuery is ready
    jQuery(document).ready(function() {
        // initialize the iFrame-Resizer
        iFrameResize({ log: true }, '#calenso-booking-widget')
    });
}else if(window.jQuery){
    console.log("page has jquery enabled, start resizer");
    iFrameResize({ log: true }, '#calenso-booking-widget')
}

