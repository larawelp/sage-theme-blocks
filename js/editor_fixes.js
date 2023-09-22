// trigger document ready again

setTimeout(function () {
    jQuery(document).trigger('readyAgain');
    if(typeof window.acf === 'undefined') {
        return;
    }
    window.acf.addAction('render_block_preview', function (el, event) {
        var doc = el[0].ownerDocument;
        jQuery(doc).trigger('readyAgain');
        const e = new Event('readyAgain');
        doc.dispatchEvent(e);
    })
}, 1000)
