document.addEventListener( 'DOMContentLoaded', () => {
	const banner = document.querySelector( '#pdfink-notice' );
	if ( banner ) {
		const dismiss_button = banner.querySelector( '.notice-dismiss' );
		dismiss_button?.addEventListener( 'click', (e) => {
			e.preventDefault();
			fetch( pdfInkLiteAjax.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams( {
					action: 'pdfink_lite_dismiss_notice',
					nonce: pdfInkLiteAjax.nonce,
					dismiss_days: dismiss_button.dataset.dismiss,
				} )
			} );
			banner.remove();
		});
	}
});