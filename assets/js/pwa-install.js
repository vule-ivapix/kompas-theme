( function() {
	var promptShell = document.querySelector( '[data-kompas-install-prompt]' );
	var installButton;
	var dismissButton;
	var deferredPrompt = null;
	var suppressKey = 'kompasPwaInstallDismissedUntil';
	var suppressDuration = 24 * 60 * 60 * 1000;

	if ( ! promptShell ) {
		return;
	}

	installButton = promptShell.querySelector( '[data-kompas-install-action="install"]' );
	dismissButton = promptShell.querySelector( '[data-kompas-install-action="dismiss"]' );

	if ( ! installButton || ! dismissButton ) {
		return;
	}

	function isStandalone() {
		return window.matchMedia( '(display-mode: standalone)' ).matches || window.navigator.standalone === true;
	}

	function showPrompt() {
		if ( isStandalone() || ! deferredPrompt || isSuppressed() ) {
			return;
		}

		promptShell.hidden = false;
		promptShell.setAttribute( 'aria-hidden', 'false' );
		promptShell.classList.add( 'is-visible' );
	}

	function hidePrompt() {
		promptShell.classList.remove( 'is-visible' );
		promptShell.setAttribute( 'aria-hidden', 'true' );
		promptShell.hidden = true;
		installButton.disabled = false;
	}

	function getSuppressedUntil() {
		var suppressedUntil = '';

		try {
			suppressedUntil = window.localStorage.getItem( suppressKey );
		} catch ( e ) {
			return 0;
		}

		if ( ! suppressedUntil ) {
			return 0;
		}

		suppressedUntil = Number( suppressedUntil );

		if ( Number.isNaN( suppressedUntil ) ) {
			clearSuppression();
			return 0;
		}

		return suppressedUntil;
	}

	function isSuppressed() {
		var suppressedUntil = getSuppressedUntil();

		if ( ! suppressedUntil ) {
			return false;
		}

		if ( suppressedUntil <= Date.now() ) {
			clearSuppression();
			return false;
		}

		return true;
	}

	function clearSuppression() {
		try {
			window.localStorage.removeItem( suppressKey );
		} catch ( e ) {}
	}

	function suppressPrompt() {
		try {
			window.localStorage.setItem( suppressKey, String( Date.now() + suppressDuration ) );
		} catch ( e ) {}
	}

	function registerServiceWorker() {
		if ( ! ( 'serviceWorker' in navigator ) ) {
			return;
		}

		if ( ! window.isSecureContext && window.location.hostname !== 'localhost' ) {
			return;
		}

		if ( ! window.kompasPwaInstallData || ! window.kompasPwaInstallData.serviceWorkerUrl ) {
			return;
		}

		navigator.serviceWorker.register(
			window.kompasPwaInstallData.serviceWorkerUrl,
			{ scope: '/' }
		).catch( function() {} );
	}

	window.addEventListener( 'beforeinstallprompt', function( event ) {
		event.preventDefault();
		deferredPrompt = event;
		showPrompt();
	} );

	window.addEventListener( 'appinstalled', function() {
		deferredPrompt = null;
		hidePrompt();
		clearSuppression();
		document.documentElement.classList.add( 'kompas-pwa-installed' );
	} );

	installButton.addEventListener( 'click', function() {
		if ( ! deferredPrompt ) {
			return;
		}

		installButton.disabled = true;
		deferredPrompt.prompt();
		deferredPrompt.userChoice.then( function( choiceResult ) {
			if ( choiceResult && choiceResult.outcome === 'dismissed' ) {
				suppressPrompt();
			}

			deferredPrompt = null;
			hidePrompt();
		} );
	} );

	dismissButton.addEventListener( 'click', function() {
		suppressPrompt();
		hidePrompt();
	} );

	if ( isStandalone() ) {
		hidePrompt();
	}

	if ( document.readyState === 'complete' ) {
		registerServiceWorker();
	} else {
		window.addEventListener( 'load', registerServiceWorker, { once: true } );
	}
} )();
