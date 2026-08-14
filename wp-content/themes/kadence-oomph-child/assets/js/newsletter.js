/**
 * Newsletter signup — keeps the visitor on this page.
 *
 * The form works without any of this. With JavaScript off it posts to
 * Plainsend and the visitor lands on a thank-you page there, which is a
 * perfectly good outcome and the reason the markup is a real <form> with a
 * real action rather than a div wired up in script.
 *
 * What this adds: submitting in the background so nobody leaves the site, a
 * message in place, and the two analytics events the CRO rules ask for (R11).
 *
 * Deliberately no dependencies and no build step, matching the rest of the
 * theme's JavaScript.
 */
( function () {
	'use strict';

	var config = window.oomphNewsletter || {};
	var form = document.querySelector( '.oomph-signup' );

	if ( ! form || ! config.endpoint ) {
		return;
	}

	var input = form.querySelector( '.oomph-signup__input' );
	var button = form.querySelector( '.oomph-signup__submit' );
	var message = form.querySelector( '.oomph-signup__message' );
	var elapsed = form.querySelector( 'input[name="elapsed_ms"]' );

	// How long the form was actually in front of a person. Plainsend treats
	// anything filled in faster than a human could read it as automated.
	// Recorded here rather than stamped by the server because these pages are
	// served from cache, so a server timestamp would be the cached one.
	var startedAt = null;
	function begin() {
		if ( startedAt === null ) {
			startedAt = Date.now();
		}
	}
	form.addEventListener( 'focusin', begin, { once: true } );
	form.addEventListener( 'keydown', begin, { once: true } );

	function say( text, isError ) {
		message.textContent = text;
		message.classList.toggle( 'is-error', !! isError );
	}

	/**
	 * R11 — every form reports a lead to GA4 and tags the Clarity session.
	 * Both are guarded: an ad blocker removing either must not break signup.
	 */
	function reportLead() {
		try {
			if ( typeof window.gtag === 'function' ) {
				window.gtag( 'event', 'generate_lead', {
					lead_source: config.source || 'footer',
				} );
			}
			if ( typeof window.clarity === 'function' ) {
				window.clarity( 'set', 'lead', config.source || 'footer' );
			}
		} catch ( e ) {
			// Analytics is never allowed to interrupt a signup.
		}
	}

	form.addEventListener( 'submit', function ( event ) {
		// Let the browser do its own required/type=email checking first, and
		// fall back to a normal navigating post if fetch is unavailable.
		if ( ! window.fetch || ! input.value ) {
			return;
		}

		event.preventDefault();

		if ( elapsed ) {
			elapsed.value = startedAt === null ? '' : String( Date.now() - startedAt );
		}

		button.disabled = true;
		say( 'Sending…', false );

		var body = new URLSearchParams( new FormData( form ) ).toString();

		// Sent as form-encoded with an Accept of JSON. Both are on the CORS
		// "simple request" list, so the browser skips the preflight round trip
		// entirely — one request instead of two.
		fetch( config.endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				Accept: 'application/json',
			},
			body: body,
		} )
			.then( function ( response ) {
				return response.json().catch( function () {
					return { ok: response.ok };
				} );
			} )
			.then( function ( result ) {
				if ( result && result.ok ) {
					form.reset();
					say( 'Check your inbox — there is one email to confirm.', false );
					reportLead();
				} else {
					button.disabled = false;
					say(
						( result && result.message ) || 'That did not go through. Please try again.',
						true
					);
				}
			} )
			.catch( function () {
				// The network failed rather than the form. Sending them to the
				// hosted page is better than asking them to try again into the
				// same broken connection.
				form.submit();
			} );
	} );
} )();
