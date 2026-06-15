/* ClickPo AI Chatbot — frontend widget logic */
( function () {
	'use strict';

	var cfg = window.ClickPoBot || {};
	var root = document.getElementById( 'clickpo-bot-root' );
	if ( ! root ) {
		return;
	}

	var launcher = document.getElementById( 'clickpo-bot-launcher' );
	var panel    = document.getElementById( 'clickpo-bot-panel' );
	var closeBtn = document.getElementById( 'clickpo-bot-close' );
	var clearBtn = document.getElementById( 'clickpo-bot-clear' );
	var messages = document.getElementById( 'clickpo-bot-messages' );
	var form     = document.getElementById( 'clickpo-bot-form' );
	var input    = document.getElementById( 'clickpo-bot-input' );
	var honeypot = document.getElementById( 'clickpo-bot-hp' );
	var submit   = form ? form.querySelector( '.clickpo-bot-submit' ) : null;

	var sessionUid = null;
	var greeted    = false;
	var busy       = false;

	var STORE_KEY = 'clickpo_bot_session';
	var OPEN_KEY  = 'clickpo_bot_open';

	function lsGet( k ) {
		try { return window.localStorage.getItem( k ); } catch ( e ) { return null; }
	}
	function lsSet( k, v ) {
		try { window.localStorage.setItem( k, v ); } catch ( e ) {}
	}
	function lsDel( k ) {
		try { window.localStorage.removeItem( k ); } catch ( e ) {}
	}

	if ( cfg.i18n ) {
		if ( input ) { input.placeholder = cfg.i18n.placeholder || ''; }
		if ( submit ) { submit.textContent = cfg.i18n.send || ''; }
	}

	function scrollDown() {
		messages.scrollTop = messages.scrollHeight;
	}

	function escapeHtml( s ) {
		return String( s )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );
	}

	// Safety net mirroring the server sanitizer: the real answer is always Hebrew and
	// comes AFTER all reasoning — keep only the Hebrew following the LAST reasoning
	// indicator (header or English meta-phrase).
	function stripReasoning( text ) {
		var tags = 'thinking|thought|thoughts|reasoning|reflection|scratchpad|internal|analysis|plan|meta|system|cot';
		text = text.replace( new RegExp( '<\\s*(' + tags + ')\\b[^>]*>[\\s\\S]*?<\\s*\\/\\s*\\1\\s*>', 'gi' ), '' );
		text = text.replace( new RegExp( '<\\s*\\/?\\s*(' + tags + ')\\b[^>]*>', 'gi' ), '' );

		var heb = /[֐-׿]/;
		var indicators = [
			/(?:^|\n)[ \t>*\-"']*(?:THOUGHT|THOUGHTS|THINKING|REASONING|REFLECTION|ANALYSIS|PLAN|CONSTRAINT\s+CHECKLIST|CHECKLIST|INTERNAL|SCRATCHPAD|META|CONFIDENCE(?:\s+SCORE)?)\b\s*:?/i,
			/\b(?:I\s+(?:must|should(?:\s+also)?|need\s+to|will|am|can\s+only|have\s+to)|my\s+instructions|the\s+user|since\s+the\s+(?:question|user)|therefore,?\s+I|as\s+an?\s+(?:ai|assistant)|let\s+me\b|i\s+can\s+only\s+help|related\s+questions|based\s+on\s+the\s+(?:provided|knowledge))\b/i,
			/[A-Za-z][A-Za-z'\-]*(?:[\s,.:;()\/]+[A-Za-z][A-Za-z'\-]*){4,}/
		];

		var had = false;
		var lastEnd = -1;
		for ( var k = 0; k < indicators.length; k++ ) {
			var g = new RegExp( indicators[ k ].source, 'gi' );
			var m, last = null;
			while ( ( m = g.exec( text ) ) !== null ) {
				last = m;
				if ( m.index === g.lastIndex ) { g.lastIndex++; }
			}
			if ( last ) {
				had = true;
				var end = last.index + last[ 0 ].length;
				if ( end > lastEnd ) { lastEnd = end; }
			}
		}

		if ( had && lastEnd >= 0 ) {
			var rest = text.slice( lastEnd );
			var h = heb.exec( rest );
			text = h ? rest.slice( h.index ) : '';
		}

		return text.replace( /\n{3,}/g, '\n\n' ).trim();
	}

	// Escape, then render minimal markdown (bold, bullets, links).
	function formatMessage( text ) {
		var cleaned = stripReasoning( String( text ) );
		if ( cleaned !== '' ) { text = cleaned; }
		var safe = escapeHtml( text );
		// Bold: **text**
		safe = safe.replace( /\*\*([^*]+)\*\*/g, '<strong>$1</strong>' );
		// Bullet list markers at line start ("* " or "- ") → bullet dot.
		safe = safe.replace( /^[ \t]*[*\-]\s+/gm, '• ' );
		// Italic: *text* (single asterisks left over).
		safe = safe.replace( /(^|[^\w*])\*(?!\s)([^*\n]+?)\*(?!\w)/g, '$1<em>$2</em>' );
		// Auto-link URLs.
		safe = safe.replace( /\b(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>' );
		return safe;
	}

	function addMessage( text, who ) {
		var el = document.createElement( 'div' );
		el.className = 'clickpo-bot-msg ' + ( who === 'user' ? 'user' : 'bot' );
		if ( who === 'user' ) {
			el.textContent = text;
		} else {
			el.innerHTML = formatMessage( text );
		}
		messages.appendChild( el );
		scrollDown();
		return el;
	}

	function addCTA( label, url ) {
		if ( ! url ) { return; }
		var a = document.createElement( 'a' );
		a.className = 'clickpo-bot-cta';
		a.href = url;
		a.target = '_blank';
		a.rel = 'noopener';
		a.textContent = label || 'מעבר';
		messages.appendChild( a );
		scrollDown();
	}

	function addSuggestions() {
		var list = cfg.suggestions || [];
		if ( ! list.length ) { return; }
		var wrap = document.createElement( 'div' );
		wrap.className = 'clickpo-bot-suggestions';
		list.forEach( function ( q ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'clickpo-bot-suggestion';
			b.textContent = q;
			b.addEventListener( 'click', function () {
				wrap.remove();
				sendMessage( q );
			} );
			wrap.appendChild( b );
		} );
		messages.appendChild( wrap );
		scrollDown();
	}

	function showTyping() {
		var label = ( cfg.i18n && cfg.i18n.typing ) ? escapeHtml( cfg.i18n.typing ) : 'מקליד';
		var el = document.createElement( 'div' );
		el.className = 'clickpo-bot-typing';
		el.id = 'clickpo-bot-typing';
		el.innerHTML = label + ' <span class="clickpo-bot-dots"><span></span><span></span><span></span></span>';
		messages.appendChild( el );
		scrollDown();
	}
	function hideTyping() {
		var el = document.getElementById( 'clickpo-bot-typing' );
		if ( el ) { el.remove(); }
	}

	function api( path, body ) {
		return fetch( cfg.restUrl + path, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify( body || {} )
		} ).then( function ( r ) {
			if ( ! r.ok ) { throw new Error( 'HTTP ' + r.status ); }
			return r.json();
		} );
	}

	function ensureSession() {
		if ( sessionUid ) { return Promise.resolve( sessionUid ); }
		return api( '/session', {} ).then( function ( res ) {
			sessionUid = res && res.session_uid ? res.session_uid : null;
			if ( sessionUid ) { lsSet( STORE_KEY, sessionUid ); }
			return sessionUid;
		} );
	}

	// Restore a saved conversation across page navigation.
	function restoreConversation() {
		var uid = lsGet( STORE_KEY );
		if ( ! uid ) { return; }
		sessionUid = uid;
		fetch( cfg.restUrl + '/history?session_uid=' + encodeURIComponent( uid ), {
			headers: { 'X-WP-Nonce': cfg.nonce }
		} )
			.then( function ( r ) {
				if ( ! r.ok ) { throw new Error( 'no history' ); }
				return r.json();
			} )
			.then( function ( res ) {
				var msgs = res && res.messages ? res.messages : [];
				if ( msgs.length ) {
					greeted = true;
					msgs.forEach( function ( m ) {
						addMessage( m.content, m.role === 'user' ? 'user' : 'bot' );
					} );
				}
				if ( lsGet( OPEN_KEY ) === '1' ) { openPanel(); }
			} )
			.catch( function () {
				// Stale/expired session — start fresh next time.
				lsDel( STORE_KEY );
				sessionUid = null;
			} );
	}

	function greet() {
		if ( cfg.welcome ) { addMessage( cfg.welcome, 'bot' ); }
		addSuggestions();
	}

	// On phones, size the full-screen panel to the visible viewport so the input
	// stays above the on-screen keyboard (iOS Safari shrinks visualViewport).
	function isMobile() {
		return window.matchMedia( '(max-width: 600px)' ).matches;
	}
	function fitMobile() {
		if ( panel.hidden || ! isMobile() || ! window.visualViewport ) {
			panel.style.height = '';
			panel.style.top = '';
			return;
		}
		var vv = window.visualViewport;
		panel.style.height = vv.height + 'px';
		panel.style.top = ( vv.offsetTop || 0 ) + 'px';
	}
	if ( window.visualViewport ) {
		window.visualViewport.addEventListener( 'resize', fitMobile );
		window.visualViewport.addEventListener( 'scroll', fitMobile );
	}
	window.addEventListener( 'resize', fitMobile );

	function openPanel() {
		panel.hidden = false;
		lsSet( OPEN_KEY, '1' );
		if ( ! greeted ) {
			greeted = true;
			greet();
		}
		fitMobile();
		if ( input ) { input.focus(); }
	}
	function closePanel() {
		panel.hidden = true;
		panel.style.height = '';
		panel.style.top = '';
		lsDel( OPEN_KEY );
	}

	function clearConversation() {
		if ( busy ) { return; }
		var msg = ( cfg.i18n && cfg.i18n.clearConfirm ) ? cfg.i18n.clearConfirm : 'Clear conversation?';
		if ( ! window.confirm( msg ) ) { return; }
		lsDel( STORE_KEY );
		sessionUid = null;
		messages.innerHTML = '';
		greeted = true;
		greet();
		if ( input ) { input.focus(); }
	}

	function sendMessage( text ) {
		if ( busy || ! text ) { return; }
		busy = true;
		addMessage( text, 'user' );
		showTyping();

		ensureSession()
			.then( function () {
				return api( '/message', {
					session_uid: sessionUid,
					message: text,
					hp: honeypot ? honeypot.value : ''
				} );
			} )
			.then( function ( res ) {
				hideTyping();
				addMessage( ( res && res.reply ) || ( cfg.i18n && cfg.i18n.error ), 'bot' );
				if ( res && res.cta && res.cta.url ) {
					addCTA( res.cta.label, res.cta.url );
				}
			} )
			.catch( function () {
				hideTyping();
				addMessage( cfg.i18n ? cfg.i18n.error : 'Error', 'bot' );
			} )
			.finally( function () {
				busy = false;
			} );
	}

	launcher.addEventListener( 'click', function () {
		panel.hidden ? openPanel() : closePanel();
	} );
	if ( closeBtn ) { closeBtn.addEventListener( 'click', closePanel ); }
	if ( clearBtn ) { clearBtn.addEventListener( 'click', clearConversation ); }

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		var text = ( input.value || '' ).trim();
		if ( ! text ) { return; }
		input.value = '';
		sendMessage( text );
	} );

	// Restore any in-progress conversation from a previous page.
	restoreConversation();
} )();
