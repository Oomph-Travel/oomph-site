/**
 * Reveal — sparse scroll-in motion for [data-reveal] elements.
 *
 * One IntersectionObserver for the whole page. The .oomph-reveal-ready
 * class on <html> gates the hiding CSS, so content is only ever hidden
 * when this script is running and able to reveal it. Reduced-motion
 * visitors never enter the system at all (both here and in the CSS
 * media query — belt and braces).
 */
(function () {
	'use strict';

	if ( ! ( 'IntersectionObserver' in window ) ) {
		return;
	}
	if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}

	var els = document.querySelectorAll( '[data-reveal]' );
	if ( ! els.length ) {
		return;
	}

	document.documentElement.classList.add( 'oomph-reveal-ready' );

	var io = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					io.unobserve( entry.target );
				}
			} );
		},
		{ rootMargin: '0px 0px -10% 0px', threshold: 0.1 }
	);

	els.forEach( function ( el ) {
		io.observe( el );
	} );
})();
