/**
 * Cabin Quiz — flow, scoring, and email-gate reveal.
 *
 * Flow: intro → one question at a time → email gate (Fluent Forms) → result.
 * Scoring honors the spec's weighting: Q5=C is a hard multi-gen override,
 * then budget tier (Q6) with a motion-sensitivity (Q1) exception.
 */
( function () {
	'use strict';

	var root = document.querySelector( '.oomph-quiz' );
	if ( ! root ) {
		return;
	}

	var steps = {
		intro:     root.querySelector( '[data-quiz-step="intro"]' ),
		questions: root.querySelector( '[data-quiz-step="questions"]' ),
		gate:      root.querySelector( '[data-quiz-step="gate"]' ),
		results:   root.querySelector( '[data-quiz-step="results"]' )
	};
	var qEls    = Array.prototype.slice.call( root.querySelectorAll( '[data-quiz-q]' ) );
	var progress = root.querySelector( '[data-quiz-progress]' );
	var total   = qEls.length;
	var answers = [];
	var current = 0;

	function show( stepName ) {
		Object.keys( steps ).forEach( function ( k ) {
			if ( steps[ k ] ) {
				steps[ k ].hidden = ( k !== stepName );
			}
		} );
	}

	function showQuestion( i ) {
		qEls.forEach( function ( el, idx ) {
			el.hidden = ( idx !== i );
		} );
		if ( progress ) {
			progress.textContent = 'Question ' + ( i + 1 ) + ' of ' + total;
		}
		// Move focus to the question for screen readers.
		var legend = qEls[ i ] && qEls[ i ].querySelector( '.oomph-quiz__question' );
		if ( legend ) {
			legend.setAttribute( 'tabindex', '-1' );
			legend.focus();
		}
		root.scrollIntoView( { behavior: 'smooth', block: 'start' } );
	}

	// Q5=C → multigen; Q6=A → suite; Q1=A + value → midship; Q6=C → porthopper; else balcony.
	function computeProfile( a ) {
		if ( a[ 4 ] === 'C' ) { return 'multigen'; }
		if ( a[ 5 ] === 'A' ) { return 'suite'; }
		if ( a[ 0 ] === 'A' && ( a[ 5 ] === 'B' || a[ 5 ] === 'C' ) ) { return 'midship'; }
		if ( a[ 5 ] === 'C' ) { return 'porthopper'; }
		return 'balcony';
	}

	function toGate() {
		var profile = computeProfile( answers );
		// Stash the result on the hidden Fluent Forms field, if present.
		var hidden = steps.gate ? steps.gate.querySelector( 'input[name="quiz_result"]' ) : null;
		if ( hidden ) {
			hidden.value = profile;
		}
		root.setAttribute( 'data-quiz-profile', profile );
		show( 'gate' );
		root.scrollIntoView( { behavior: 'smooth', block: 'start' } );
	}

	function revealResult() {
		var profile = root.getAttribute( 'data-quiz-profile' ) || computeProfile( answers );
		var panels = root.querySelectorAll( '.oomph-quiz__result' );
		Array.prototype.forEach.call( panels, function ( p ) {
			p.hidden = ( p.getAttribute( 'data-profile' ) !== profile );
		} );
		show( 'results' );
		root.scrollIntoView( { behavior: 'smooth', block: 'start' } );

		// GA4 lead event (no-op until GA4 is present).
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( { event: 'generate_lead', lead_source: 'cabin_quiz', cabin_profile: profile } );
		if ( typeof window.gtag === 'function' ) {
			window.gtag( 'event', 'generate_lead', { lead_source: 'cabin_quiz', cabin_profile: profile } );
		}
		if ( typeof window.clarity === 'function' ) {
			window.clarity( 'set', 'lead', 'cabin_quiz' );
		}
	}

	// Start.
	var startBtn = root.querySelector( '[data-quiz-start]' );
	if ( startBtn ) {
		startBtn.addEventListener( 'click', function () {
			answers = [];
			current = 0;
			show( 'questions' );
			showQuestion( 0 );
		} );
	}

	// Answer an option.
	root.addEventListener( 'click', function ( e ) {
		var opt = e.target.closest( '.oomph-quiz__opt' );
		if ( opt ) {
			answers[ current ] = opt.getAttribute( 'data-letter' );
			if ( current < total - 1 ) {
				current++;
				showQuestion( current );
			} else {
				toGate();
			}
			return;
		}
		var back = e.target.closest( '[data-quiz-back]' );
		if ( back ) {
			if ( current > 0 ) {
				current--;
				showQuestion( current );
			}
		}
	} );

	// Fluent Forms fires this on document.body after a successful AJAX submit.
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'fluentform_submission_success', function () {
			// Only react when the quiz email gate is the active step.
			if ( steps.gate && ! steps.gate.hidden ) {
				revealResult();
			}
		} );
	}
}() );
