document.addEventListener( 'DOMContentLoaded', () => {

	const banner = document.querySelector( '#pdfink-notice' );
	if ( banner ) {
		const dismiss_button = banner.querySelector( '.notice-dismiss' );
		dismiss_button?.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			fetch( pdfink_script.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams( {
					action: 'pdfink_lite_dismiss_notice',
					nonce: pdfink_script.notice_nonce,
					dismiss_days: dismiss_button.dataset.dismiss,
				} )
			} );
			banner.remove();
		} );
	}
} );

const doPost = ( task ) => fetch( pdfink_script.ajax_url, {
	method: 'POST',
	headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
	body: new URLSearchParams( {
		action: 'wwpdf_debug_log_action',
		nonce: pdfink_script.logging_nonce,
		task,
	} )
} );

document.addEventListener( 'click', async ( e ) => {

	if ( e.target.closest( '#wwpdf-download-debug-log' ) ) {
		const res  = await doPost( 'download' );
		const json = await res.json();
		if ( json.success ) {
			const blob = new Blob( [ json.data.contents ], { type: 'text/plain' } );
			const url  = URL.createObjectURL( blob );
			const a    = Object.assign( document.createElement( 'a' ), {
				href: url, download: 'pdfink-debug-log.txt',
			} );
			a.click();
			URL.revokeObjectURL( url );
		}
		return;
	}

	if ( e.target.closest( '#wwpdf-clear-debug-log' ) ) {
		const res  = await doPost( 'clear' );
		const json = await res.json();
		if ( json.success ) {
			document.querySelector( '#wwpdf-log-textarea' ).value = json.data.contents ?? '';
		} else {
			alert( json.data?.message || 'Something went wrong.' );
		}
		return;
	}

	if ( e.target.closest( '#wwpdf-copy-debug-log' ) ) {
		const ta = document.querySelector( '#wwpdf-log-textarea' );
		await navigator.clipboard.writeText( ta.value );
	}

} );