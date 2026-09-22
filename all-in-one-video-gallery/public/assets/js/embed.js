(function() {
    'use strict';

    const AIOVGTemplate = document.createElement( 'template' );

    AIOVGTemplate.innerHTML = `
        <style>
            :host {                             
                display: block;  
                border-radius: 3px;
                width: 100%;      
                contain: content;
                overflow: hidden;
            }

            :host([hidden]) {
                display: none;
            }

            #root {            
                display: block;
                position: relative;
                cursor: pointer;
                padding-bottom: calc(100% / (16 / 9));
                width: 100%;
                height: 0;   
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;        
            }
        
            #posterimage, 
            iframe {
                position: absolute;
                top: 0;
                left: 0; 
                width: 100%;
                height: 100%;                     
            }        

            #posterimage {
                object-fit: cover;            
                transition: 0.3s;
                opacity: 1;
            }

            iframe {            
                z-index: 1;
                border: 0;
            }

            #playbutton {
                display: none;
            }

            /* YouTube */
            .youtube > #playbutton {
                display: block;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate3d(-50%, -50%, 0); 
                transition: all 0.2s cubic-bezier(0, 0, 0.2, 1); 
                z-index: 1;
                border: 0;        
                background: center/72px 48px no-repeat url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 72 48'%3E%3Cpath fill='%23f00' fill-opacity='.9' d='M66.5 7.7c-.8-2.9-2.5-5.4-5.4-6.2C55.8.1 34 0 34 0S12.2.1 6.9 1.6c-3 .7-4.6 3.2-5.4 6.1a89.6 89.6 0 000 32.5c.8 3 2.5 5.5 5.4 6.3C12.2 47.9 34 48 34 48s21.8-.1 27.1-1.6c3-.7 4.6-3.2 5.4-6.1C68 35 68 24 68 24s0-11-1.5-16.3z'/%3E%3Cpath fill='%23fff' d='M45 24L27 14v20'/%3E%3C/svg%3E");
                cursor: pointer;
                width: 72px;
                height: 48px;
                filter: grayscale(1);   
            }       
            
            .youtube:hover > #playbutton,
            .youtube > #playbutton:focus {
                filter: none;
            }

            /* Vimeo */
            .vimeo > #playbutton {
                display: flex;
                position: absolute;
                top: 50%;
                left: 50%;
                align-items: center;
                justify-content: center;
                transform: translate3d(-50%, -50%, 0);
                transition: all 0.2s cubic-bezier(0, 0, 0.2, 1);  
                opacity: 0.8; 
                z-index: 1;
                border: 0;
                border-radius: 8px;
                background: rgba(23, 35, 34, .75);
                cursor: pointer;            
                width: 72px;
                height: 48px;
            }  
            
            .vimeo > #playbutton:after {
                border-width: 10px 0 10px 20px;     
                border-style: solid;      
                border-color: transparent transparent transparent #fff; 
                content: "";
            }      
            
            .vimeo:hover > #playbutton,
            .vimeo > #playbutton:focus {
                opacity: 1;
                background: rgb(98, 175, 237);       
            }

            /* Dailymotion */
            .dailymotion > #playbutton {
                display: block;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate3d(-50%, -50%, 0);
                transition: all 0.2s cubic-bezier(0, 0, 0.2, 1); 
                opacity: 1;
                z-index: 1;
                border: 0;
                border-radius: 40px;
                background-color: rgba(13, 13, 13, 0.6);
                background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M8.56047 5.09337C8.34001 4.9668 8.07015 4.96875 7.85254 5.10019C7.63398 5.23162 7.5 5.47113 7.5 5.73011L7.5 18.2698C7.5 18.5298 7.63398 18.7693 7.85254 18.9007C7.96372 18.9669 8.0882 19 8.21268 19C8.33241 19 8.45309 18.9688 8.56047 18.9075L18.1351 12.6377C18.3603 12.5082 18.5 12.2648 18.5 12C18.5 11.7361 18.3603 11.4917 18.1351 11.3632L8.56047 5.09337Z' fill='%23fff'%3E%3C/path%3E%3C/svg%3E");
                background-position: center;
                background-size: 64px; 
                cursor: pointer;            
                width: 80px;
                height: 80px;
            }     
            
            .dailymotion:hover > #playbutton,
            .dailymotion > #playbutton:focus {
                opacity: 0.8;
            }

            /* Cookie consent */
            #cookieconsent-modal {
                display: none;
                position: absolute;
                inset: 0;
                z-index: 1;
                align-items: flex-start;
                justify-content: center;
                background-color: rgba(0, 0, 0, 0.85);
                width: 100%;
                height: 100%;
                overflow-y: auto;
                line-height: 1.5;
                color: #fff;
                font-size: 14px;
            }

            #root.cookieconsent {
                cursor: unset;
            }

            #root.cookieconsent > #playbutton {
                display: none;
            }

            #root.cookieconsent > #cookieconsent-modal {
                display: flex;
            }

            .cookieconsent-block {
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin: auto;
                padding: 16px 24px;
                width: 100%;
                max-width: 480px;
                text-align: center;
            }

            .cookieconsent-button,
            .cookieconsent-button:hover,
            .cookieconsent-button:focus {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 0;
                border-radius: 3px;
                box-shadow: none;
                background: none;
                background-color: var(--aiovg-color-primary, #3b82f6);
                background-image: none;
                cursor: pointer;
                padding: 0 20px;
                min-height: 44px;
                line-height: 1;
                color: #fff;
                font-family: inherit;
                font-size: 1em;
                font-weight: 600;
            }

            .cookieconsent-button:hover {
                opacity: 0.85;
            }

            /* Email Capture */
            #email-capture-modal {
                display: none;
                position: absolute;
                inset: 0;
                z-index: 2;
                align-items: flex-start;
                justify-content: center;
                background-color: rgba(0, 0, 0, 0.85);
                width: 100%;
                height: 100%;
                overflow-y: auto;
                line-height: 1.5;
                color: #fff;
                font-size: 14px;
            }

            #root.email-capture {
                cursor: unset;
            }

            #root.email-capture > #playbutton {
                display: none;
            }

            #root.email-capture > #email-capture-modal {
                display: flex;
            }

            .email-capture {
                display: contents;
            }

            .email-capture-block {
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin: auto;
                padding: 16px 24px;
                width: 100%;
                max-width: 480px;
            }

            .email-capture-header {
                text-align: center;
                text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
                font-size: 1.15em;
            }

            .email-capture-fields {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .email-capture-fields input {
                box-sizing: border-box;
                display: block;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 3px;
                background: rgba(255, 255, 255, 0.12);
                padding: 0 12px;
                width: 100%;
                min-height: 44px;
                line-height: 1;
                color: #fff;
                font-family: inherit;
                font-size: 1em;
            }

            .email-capture-fields input::placeholder {
                color: rgba(255, 255, 255, 0.5);
            }

            .email-capture-fields input:focus {
                outline: none;
                border-color: rgba(255, 255, 255, 0.6);
                background: rgba(255, 255, 255, 0.18);
            }

            .email-capture-email.is-invalid {
                border-color: #ff7070;
            }

            .email-capture-error {
                display: none;
                margin-top: -4px;
                color: #ff7070;
                font-size: 0.875em;
            }

            .email-capture-error::before {
                content: "⚠";
                margin-right: 5px;
            }

            .email-capture-footer {
                margin-top: -4px;
                line-height: 1.4;
                color: rgba(255, 255, 255, 0.55);
                font-size: 0.875em;
            }

            .email-capture-footer a {
                color: rgba(255, 255, 255, 0.75);
                text-decoration: underline;
            }

            .email-capture-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: center;
                gap: 12px;
            }

            .email-capture-submit,
            .email-capture-submit:hover,
            .email-capture-submit:focus {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 0;
                border-radius: 3px;
                box-shadow: none;
                background: none;
                background-color: var(--aiovg-color-primary, #3b82f6);
                background-image: none;
                cursor: pointer;
                padding: 0 20px;
                min-height: 44px;
                line-height: 1;
                color: #fff;
                font-family: inherit;
                font-size: 1em;
                font-weight: 600;
            }

            .email-capture-submit:hover {
                opacity: 0.85;
            }

            .email-capture-skip {
                display: inline-flex;
                align-items: center;
                cursor: pointer;
                text-decoration: underline;
                color: rgba(255, 255, 255, 0.6);
                font-size: 0.875em;
            }

            .email-capture-skip:hover {
                color: #fff;
            }

            @keyframes spin {
                to { transform: rotate( 360deg ); }
            }

            .is-loading .email-capture-block * {
                display: none !important;
            }

            .is-loading .email-capture-block {
                pointer-events: none;
                margin: auto;
                border: 3px solid rgba( 255, 255, 255, 0.25 );
                border-radius: 50%;
                border-top-color: #fff;    
                padding: 0;        
                width: 36px;
                height: 36px;
                animation: spin 0.7s linear infinite;            
            }

            /* Post-click styles */
            #root.initialized {
                cursor: unset;
            }

            #root.initialized > #posterimage,
            #root.initialized > #playbutton {            
                pointer-events: none;
                opacity: 0;
            }
        </style>
        <div id="root">
            <img id="posterimage" src="data:image/gif;base64,R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=" alt="" referrerpolicy="origin" />
            <button type="button" id="playbutton" aria-label="Play Video"></button>
            <div id="cookieconsent-modal">
                <div class="cookieconsent-block">
                    <div><slot name="cookieconsent-message"></slot></div>
                    <div>
                        <button type="button" id="cookieconsent-button" class="cookieconsent-button">
                            <slot name="cookieconsent-button-label">I Agree</slot>
                        </button>
                    </div>
                </div>
            </div>
            <div id="email-capture-modal"></div>
        </div>
    `;

    class AIOVGBaseElement extends HTMLElement {

        /**
         * Element created.
         */
        constructor() {
            super();        
            
            // Attach Shadow DOM to the component
            const shadowDom = this.attachShadow({ mode: 'open' });
            this.shadowRoot.appendChild( AIOVGTemplate.content.cloneNode( true ) );        

            // Set references to the DOM elements from the component's template
            this.$root = shadowDom.querySelector( '#root' );
            this.$posterImage = shadowDom.querySelector( '#posterimage' );
            this.$playButton = shadowDom.querySelector( '#playbutton' );
            this.$cookieConsentButton = shadowDom.querySelector( '#cookieconsent-button' );
            this.$emailCaptureModal = shadowDom.querySelector( '#email-capture-modal' );

            // Set references to the private properties used by the component
            this._isRendered = false;
            this._ajaxUrl = aiovg_embed.ajax_url;
            this._ajaxNonce = aiovg_embed.ajax_nonce;
            this._isCookieConsentLoaded = false;
            this._isEmailCaptureLoaded = false;
            this._isPosterImageLoaded = false;
            this._isIframeLoaded = false;             
            this._forceIframeElement = navigator.vendor.includes( 'Apple' ) || navigator.userAgent.includes( 'Mobi' ); 
            this._intersectionObserver = null;
            this._isInViewport = false;
        }

        /**
         * Browser calls this method when the element is added to the document.
         * (can be called many times if an element is repeatedly added/removed)
         */
        connectedCallback() { 
            if ( ! this.src ) return false;

            if ( ! this.lazyLoading ) {
                this._forceIframeElement = true;
            }
            
            if ( ! this.poster ) {
                this._forceIframeElement = true;
            }

            if ( ! this._forceIframeElement ) {
                const url = new URL( this.src );
                const query = new URLSearchParams( url.search );            
                const autoplayRequested = query.has( 'autoplay' ) && ( query.get( 'autoplay' ) == 1 || query.get( 'autoplay' ) == true );    
                
                if ( autoplayRequested ) this._forceIframeElement = true;
            }        
        
            this._render();

            this.addEventListener( 'pointerover', () => this._warmConnections(), { once: true, } );
            this.addEventListener( 'click', () => this._addIframe( true ) );
        }

        /**
         * Browser calls this method when the element is removed from the document.
         * (can be called many times if an element is repeatedly added/removed)
         */
        disconnectedCallback() {
            // TODO
        }

        /**
         * Array of attribute names to monitor for changes.
         */
        static get observedAttributes() {
            return [ 'title', 'ratio' ];
        }   
        
        /**
         * Called when one of the observed attributes listed above is modified.
         */
        attributeChangedCallback( name, oldValue, newValue ) {
            if ( oldValue == newValue ) return false;

            switch ( name ) {
                case 'title':
                    // Set attributes for accessibility
                    if ( newValue ) {
                        this.$posterImage.setAttribute( 'alt', newValue );  
                    } else {
                        this.$posterImage.setAttribute( 'alt', '' );
                    }
                    break;

                case 'ratio':      
                    if ( newValue ) {               
                        this.$root.style.paddingBottom = `${parseFloat(newValue)}%`; 
                    }
                    break;
            }
        }

        /**
         * Define getters and setters for attributes.
         */

        get title() {
            return this.getAttribute( 'title' ) || '';
        }

        get src() {
            const value = this.getAttribute( 'src' ) || '';
            return AIOVGBaseElement.isValidUrl( value ) ? value : '';
        }

        get poster() {
            const value = this.getAttribute( 'poster' ) || '';
            return AIOVGBaseElement.isValidUrl( value ) ? value : '';
        }

        get postId() {
            return parseInt( this.getAttribute( 'post_id' ) || 0 );
        }  
        
        get postType() {
            return this.getAttribute( 'post_type' ) || '';
        }

        get lazyLoading() {
            return this.hasAttribute( 'lazyloading' );
        }

        get statistics() {
            return this.hasAttribute( 'statistics' );
        }

        get cookieConsent() {
            return this.hasAttribute( 'cookieconsent' );
        }

        set cookieConsent( value ) {
            const isEnabled = Boolean( value );

            if ( isEnabled ) {
                this.setAttribute( 'cookieconsent', '' );
            } else {
                this.removeAttribute( 'cookieconsent' );
            }
        }

        get emailCapture() {
            return this.hasAttribute( 'email-capture' );
        }

        set emailCapture( value ) {
            const isEnabled = Boolean( value );

            if ( isEnabled ) {
                this.setAttribute( 'email-capture', '' );
            } else {
                this.removeAttribute( 'email-capture' );
            }
        }

        /**
         * Define private methods.
         */

        _render() {    
            if ( this._isRendered ) return false;          
            
            if ( this.lazyLoading && ! this._isInViewport ) {
                this._initIntersectionObserver();
                return false;
            }
            
            if ( this.emailCapture ) {
                this._addEmailCapture();
                return false;
            }

            if ( this.cookieConsent ) {
                this._addCookieConsent();
                return false;
            }

            this._isRendered = true;

            if ( this._forceIframeElement ) {
                this._warmConnections();
                this._addIframe();
            } else {                    
                this._addPosterImage();    
            }
        }

        _addCookieConsent() {
            if ( this._isCookieConsentLoaded ) return false; 
            this._isCookieConsentLoaded = true;

            this._addPosterImage();           
            this._addClass( 'cookieconsent' );

            this.$cookieConsentButton.addEventListener( 'click', () => this._onCookieConsent() );
        }

        _onCookieConsent() {   
            this._isRendered = true;
                
            this._removeClass( 'cookieconsent' );
            this.cookieConsent = false;        

            this._warmConnections();
            this._addIframe( true );

            this._setCookie();

            // Remove cookieconsent from other players
            const videos = document.querySelectorAll( '.aiovg-player-element' );
            for ( let i = 0; i < videos.length; i++ ) {
                videos[ i ].removeCookieConsent();
            }
            
            window.postMessage({                       
                message: 'aiovg-cookie-consent'
            }, window.location.origin );
        }

        _addEmailCapture() {
            if ( this._isEmailCaptureLoaded ) return false;
            this._isEmailCaptureLoaded = true;

            // Read template from light DOM child placed by PHP
            const templateEl = this.querySelector( 'template.aiovg-player-template' );
            if ( ! templateEl ) return false;

            // Clone HTML from template into shadow DOM
            const emailCaptureEl = templateEl.content.querySelector( '.email-capture' );
            if ( ! emailCaptureEl ) return false;
            
            // Move all children of email capture block into the email capture modal container in the shadow DOM
            this.$emailCaptureModal.appendChild( emailCaptureEl.cloneNode( true ) );

            // Add poster image if available and enable the email capture modal by adding the corresponding class to the root element
            this._addPosterImage();         
            this._addClass( 'email-capture' );

            // Which video this is. The skip path sends it too: without it the server
            // cannot derive a capture key, and "Once per video" would never remember
            // the skip, asking again on every play.
            const videoParams = () => {
                if ( 'aiovg_videos' === this.postType && this.postId ) {
                    return '&video_id=' + encodeURIComponent( this.postId );
                }

                let params = '&video_id=0';

                if ( this.src ) {
                    params += '&video_url=' + encodeURIComponent( this.src );
                }

                return params;
            };

            // Focus the first input for better accessibility
            const firstInput = this.$emailCaptureModal.querySelector( 'input' );
            if ( firstInput && ! firstInput.value ) {
                firstInput.focus();
            }

            // Submit handler
            const submitBtn = this.$emailCaptureModal.querySelector( '.email-capture-submit' );

            if ( submitBtn ) {
                submitBtn.addEventListener( 'click', () => {
                    const emailField = this.$emailCaptureModal.querySelector( '.email-capture-email' );
                    const errorSpan  = this.$emailCaptureModal.querySelector( '.email-capture-error' );

                    const email = emailField ? emailField.value.trim() : '';
                    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    if ( ! email || ! regex.test( email ) ) {
                        if ( errorSpan ) {
                            const errorMessage = errorSpan.getAttribute( 'data-invalid_email' ) || 'Please enter a valid email address.';
                            
                            errorSpan.textContent = errorMessage;
                            errorSpan.style.display = 'block';
                        }

                        if ( emailField ) {
                            emailField.classList.add( 'is-invalid' );
                            emailField.setAttribute( 'aria-invalid', 'true' );
                        }

                        return false;
                    }

                    if ( errorSpan ) {
                        errorSpan.style.display = 'none';
                    }

                    if ( emailField ) {
                        emailField.classList.remove( 'is-invalid' );
                        emailField.removeAttribute( 'aria-invalid' );
                    }

                    let params = 'email=' + encodeURIComponent( email );

                    const nameField = this.$emailCaptureModal.querySelector( '.email-capture-name' );
                    if ( nameField && nameField.value.trim() ) {
                        params += '&name=' + encodeURIComponent( nameField.value.trim() );
                    }

                    const phoneField = this.$emailCaptureModal.querySelector( '.email-capture-phone' );
                    if ( phoneField && phoneField.value.trim() ) {
                        params += '&phone=' + encodeURIComponent( phoneField.value.trim() );
                    }

                    params += videoParams();
                    params += '&page_url=' + encodeURIComponent( window.location.href );

                    this._onEmailCapture( params );
                } );
            }

            // Skip handler
            const skipLink = this.$emailCaptureModal.querySelector( '.email-capture-skip' );

            if ( skipLink ) {
                const doSkip = () => {
                    this._onEmailCapture( 'skipped=1' + videoParams() );
                };

                skipLink.addEventListener( 'click', doSkip );

                skipLink.addEventListener( 'keydown', ( event ) => {
                    if ( 13 === event.keyCode || 32 === event.keyCode ) {
                        event.preventDefault();
                        doSkip();
                    }
                } );
            }
        }

        _onEmailCapture( data ) {
            if ( this.$emailCaptureModal.classList.contains( 'is-loading' ) ) return false; // Prevent multiple submissions
            this.$emailCaptureModal.classList.add( 'is-loading' );

            const xmlhttp = new XMLHttpRequest();

            xmlhttp.onreadystatechange = () => {
                if ( 4 == xmlhttp.readyState ) {
                    let response = null;

                    if ( 200 == xmlhttp.status && xmlhttp.responseText ) {
                        try {
                            response = JSON.parse( xmlhttp.responseText );
                        } catch ( e ) {}
                    }

                    if ( response && response.success ) {
                        // Email captured successfully, proceed to load the video and remove the email capture gate
                        this._isRendered = true;
                
                        this._removeClass( 'email-capture' );
                        this.emailCapture = false;        

                        this._warmConnections();
                        this._addIframe( true ); 

                        // Per-video scoping. An empty key means "once per visitor", where one
                        // capture clears every player on the page.
                        const captureKey = this.getAttribute( 'capture-key' ) || '';

                        // A skip the server did not remember clears only the consent gates.
                        const remembered = ! ( response.data && false === response.data.remembered );

                        // Email submission implies consent — remove both gates from other players
                        const videos = document.querySelectorAll( '.aiovg-player-element' );
                        for ( let i = 0; i < videos.length; i++ ) {
                            videos[ i ].removeCookieConsent();

                            if ( remembered ) {
                                videos[ i ].removeEmailCapture( captureKey );
                            }
                        }

                        window.postMessage( remembered ? {
                            message: 'aiovg-email-captured',
                            capture_key: captureKey
                        } : {
                            message: 'aiovg-cookie-consent'
                        }, window.location.origin );
                    } else {
                        // Show error message
                        const errorSpan = this.$emailCaptureModal.querySelector( '.email-capture-error' );

                        if ( errorSpan ) {
                            errorSpan.textContent   = ( response && response.data && typeof response.data === 'string' ) ? response.data : errorSpan.getAttribute( 'data-error_generic' );
                            errorSpan.style.display = 'block';
                        }

                        // Restore the form so the user can retry.
                        this.$emailCaptureModal.classList.remove( 'is-loading' );
                    }
                }
            };

            xmlhttp.open( 'POST', this._ajaxUrl + '?action=aiovg_save_email_lead&security=' + this._ajaxNonce, true );
            xmlhttp.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
            xmlhttp.send( data );                      
        }

        _addPosterImage() {
            if ( this._isPosterImageLoaded ) return false;
            this._isPosterImageLoaded = true;

            if ( this.poster ) {
                this.$posterImage.src = this.poster;
            }
        }

        _addIframe( autoplayRequested = false ) {
            if ( this._isIframeLoaded || this.emailCapture || this.cookieConsent ) return false;  
            this._isIframeLoaded = true;  

            const iframeEl = document.createElement( 'iframe' );
            
            iframeEl.width = 560;
            iframeEl.height = 315;
        
            iframeEl.title = this.title;
            
            iframeEl.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            iframeEl.allowFullscreen = true;

            if ( /youtube\.com|youtu\.be/.test( this.src ) ) {
                iframeEl.referrerPolicy = 'strict-origin-when-cross-origin';
            }

            if ( autoplayRequested ) {
                const url = new URL( this.src );

                let searchParams = url.searchParams;
                searchParams.set( 'autoplay', 1 );

                url.search = searchParams.toString();

                iframeEl.src = url.toString();
            } else {
                iframeEl.src = this.src;
            }

            this.$root.append( iframeEl );

            this._onIframeAdded( iframeEl );

            this._addClass( 'initialized' );
            this._updateViewsCount();        
        }

        _initIntersectionObserver() {
            if ( this._intersectionObserver ) return false;

            const options = {
                root: null,
                rootMargin: '0px',
                threshold: 0,
            };

            this._intersectionObserver = new IntersectionObserver(( entries, observer ) => {
                entries.forEach(entry => {
                    if ( entry.isIntersecting ) {
                        this._isInViewport = true;
                        this._render();

                        if ( this._isRendered ) observer.unobserve( this );
                    } else {
                        this._isInViewport = false;
                    }
                });
            }, options);

            this._intersectionObserver.observe( this );
        } 

        _hasClass( className ) {
            return this.$root.classList.contains( className );
        }
        
        _addClass( className ) {
            this.$root.classList.add( className );
        }

        _removeClass( className ) {
            this.$root.classList.remove( className );
        }

        /**
         * Define private async methods.
         */

        async _updateViewsCount() {
            if ( this.statistics && this.postType == 'aiovg_videos' ) {
                let formData = new FormData();
                formData.append( 'action', 'aiovg_update_views_count' );
                formData.append( 'post_id', this.postId );
                formData.append( 'security', this._ajaxNonce );

                fetch( this._ajaxUrl, { method: 'POST', body: formData } );
            }
        }
        
        async _setCookie() {
            let formData = new FormData();
            formData.append( 'action', 'aiovg_set_cookie' );
            formData.append( 'security', this._ajaxNonce );

            fetch( this._ajaxUrl, { method: 'POST', body: formData } );
        }

        /**
         * Define public methods.
         */

        _warmConnections() {
            // Always overridden by the child classes
        }

        _onIframeAdded() {
            // Always overridden by the child classes
        }

        /**
         * Define public static methods.
         */

        static isValidUrl( url ) {
            if ( url == '' ) return false;

            try {
                new URL( url );
                return true;
            } catch ( error ) {
                return false;
            }
        }

        static addPrefetch( kind, url ) {
            const linkElem = document.createElement( 'link' );
            linkElem.rel = kind;
            linkElem.href = url;
            linkElem.crossOrigin = 'true';

            document.head.append( linkElem );
        }
        
        /**
         * Define API methods.
         */

        removeCookieConsent() {
            if ( this._isRendered ) return false;

            this._removeClass( 'cookieconsent' );
            this.cookieConsent = false;

            this._render();
        }

        removeEmailCapture( captureKey ) {
            if ( this._isRendered ) return false;

            // Per-video scoping. No key means "once per visitor", where any capture clears
            // every player; a key clears only the player showing that same video.
            if ( captureKey && this.getAttribute( 'capture-key' ) !== captureKey ) {
                return false;
            }

            this._removeClass( 'email-capture' );
            this.emailCapture = false;

            this._render();
        }

        play() {
            // Always overridden by the child classes
        }

        pause() {
            // Always overridden by the child classes
        }

        seekTo( seconds ) {
            // Always overridden by the child classes
        }

    }

    class AIOVGYouTubeElement extends AIOVGBaseElement {

        constructor() {
            super();
            this._addClass( 'youtube' );

            // Set references to the private properties used by the component
            this._player = null;
        }

        /**
         * Browser calls this method when the element is removed from the document.
         * (can be called many times if an element is repeatedly added/removed)
         */
        disconnectedCallback() {
            if ( this._player && this._player.destroy ) {
                this._player.destroy();
            }

            this._player = null;

            if ( super.disconnectedCallback ) {
                super.disconnectedCallback();
            }
        }

        /**
         * Define public methods.
         */

        _warmConnections() {
            if ( window.AIOVGYouTubeIsPreconnected ) return false;

            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://i.ytimg.com' );
            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://s.ytimg.com' );
            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://yt3.ggpht.com' );

            if ( this.src.indexOf( 'www.youtube-nocookie.com' ) > -1 ) {
                AIOVGBaseElement.addPrefetch( 'preconnect', 'https://www.youtube-nocookie.com' );
            } else {
                AIOVGBaseElement.addPrefetch( 'preconnect', 'https://www.youtube.com' );
            }

            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://www.google.com' );
            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://googleads.g.doubleclick.net' );
            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://static.doubleclick.net' );

            window.AIOVGYouTubeIsPreconnected = true;
        }

        _onIframeAdded( iframeEl ) {
            this._loadApi().then(() => {
                const player = new YT.Player( iframeEl, {
                    events: {
                        'onReady': () => {
                            this._player = player;
                        },
                        'onStateChange': ( event ) => {
                            if ( 1 == event.data ) { // Playing
                                // Pause other players
                                const videos = document.querySelectorAll( '.aiovg-player-element' );
                                for ( let i = 0; i < videos.length; i++ ) {
                                    if ( videos[ i ] != this ) {
                                        videos[ i ].pause();
                                    }
                                }

                                window.postMessage({					 				
                                    message: 'aiovg-video-playing'
                                }, window.location.origin );
                            } 
                        }
                    }
                });
            });
        }

        /**
         * Define private methods.
         */

        _loadApi() {
            return new Promise(( resolve ) => { 
                if ( typeof window.YT === 'undefined' && typeof AIOVGYouTubeElement.isApiLoaded === 'undefined' ) {
                    AIOVGYouTubeElement.isApiLoaded = true;

                    const tag = document.createElement( 'script' );
                    tag.src = 'https://www.youtube.com/iframe_api';
                    
                    const firstScriptTag = document.getElementsByTagName( 'script' )[0];
                    firstScriptTag.parentNode.insertBefore( tag, firstScriptTag );	
                }		

                if ( typeof window.YT !== 'undefined' && window.YT.Player )	{
                    resolve();	
                } else {		
                    let intervalHandler = setInterval(function() {
                        if ( typeof window.YT !== 'undefined' && window.YT.Player )	{
                            clearInterval( intervalHandler );
                            resolve();	
                        }
                    }, 100 );
                }
            });
        }

        /**
         * Define API methods.
         */

        play() {
            if ( this._player && this._player.playVideo ) {
                this._player.playVideo();
            }
        }

        pause() {
            if ( this._player && this._player.pauseVideo ) {
                this._player.pauseVideo();
            }
        }

        seekTo( seconds ) {
            if ( this._player && this._player.seekTo ) {
                this._player.seekTo( seconds, true );
            }
        }

    }

    class AIOVGVimeoElement extends AIOVGBaseElement {

        constructor() {
            super();
            this._addClass( 'vimeo' );

            // Set references to the private properties used by the component
            this._player = null;
        }

        /**
         * Browser calls this method when the element is removed from the document.
         * (can be called many times if an element is repeatedly added/removed)
         */
        disconnectedCallback() {
            if ( this._player && this._player.destroy ) {
                this._player.destroy();
            }

            this._player = null;
            
            if ( super.disconnectedCallback ) {
                super.disconnectedCallback();
            }
        }

        /**
         * Define public methods.
         */

        _warmConnections() {
            if ( window.AIOVGVimeoIsPreconnected ) return false;

            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://i.vimeocdn.com' );
            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://player.vimeo.com' );

            window.AIOVGVimeoIsPreconnected = true;
        }

        _onIframeAdded( iframeEl ) {
            this._loadApi().then(() => {
                const player = new Vimeo.Player( iframeEl );

                player.ready().then(() => {
                    this._player = player;
                });

                player.on( 'play', ( data ) => {
                    // Pause other players
                    const videos = document.querySelectorAll( '.aiovg-player-element' );
                    for ( let i = 0; i < videos.length; i++ ) {
                        if ( videos[ i ] != this ) {
                            videos[ i ].pause();
                        }
                    }

                    window.postMessage({					 				
                        message: 'aiovg-video-playing'
                    }, window.location.origin );
                });
            });
        }

        /**
         * Define private methods.
         */

        _loadApi() {
            return new Promise(( resolve ) => { 
                if ( typeof window.Vimeo === 'undefined' && typeof AIOVGVimeoElement.isApiLoaded === 'undefined' ) {
                    AIOVGVimeoElement.isApiLoaded = true;

                    const tag = document.createElement( 'script' );
                    tag.src = 'https://player.vimeo.com/api/player.js';
                    
                    const firstScriptTag = document.getElementsByTagName( 'script' )[0];
                    firstScriptTag.parentNode.insertBefore( tag, firstScriptTag );	
                }		

                if ( typeof window.Vimeo !== 'undefined' && window.Vimeo.Player )	{
                    resolve();	
                } else {		
                    let intervalHandler = setInterval(function() {
                        if ( typeof window.Vimeo !== 'undefined' && window.Vimeo.Player )	{
                            clearInterval( intervalHandler );
                            resolve();	
                        }
                    }, 100 );
                }
            });
        }

        /**
         * Define API methods.
         */

        play() {
            if ( this._player && this._player.play ) {
                this._player.play();
            }
        }

        pause() {
            if ( this._player && this._player.pause ) {
                this._player.pause();
            }
        }

        seekTo( seconds ) {
            if ( this._player && this._player.setCurrentTime ) {
                this._player.setCurrentTime( seconds );
            }
        }

    }

    class AIOVGDailymotionElement extends AIOVGBaseElement {

        constructor() {
            super();
            this._addClass( 'dailymotion' );
        }

        _warmConnections() {
            if ( window.AIOVGDailymotionIsPreconnected ) return false;

            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://s1.dmcdn.net' );
            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://s2.dmcdn.net' );
            AIOVGBaseElement.addPrefetch( 'preconnect', 'https://www.dailymotion.com' );

            window.AIOVGDailymotionIsPreconnected = true;
        }

    }

    class AIOVGIframeElement extends AIOVGBaseElement {

        constructor() {
            super();
            this._forceIframeElement = true;
        }

    }

    window.AIOVGYouTubeIsPreconnected = false;
    window.AIOVGVimeoIsPreconnected = false;
    window.AIOVGDailymotionIsPreconnected = false;

    /**
     * Called when the page has loaded.
     */
    document.addEventListener( 'DOMContentLoaded', function() {
        // Register custom elements
        if ( ! customElements.get( 'aiovg-youtube' ) ) {
            customElements.define( 'aiovg-youtube', AIOVGYouTubeElement );
        }

        if ( ! customElements.get( 'aiovg-vimeo' ) ) {
            customElements.define( 'aiovg-vimeo', AIOVGVimeoElement );
        }

        if ( ! customElements.get( 'aiovg-dailymotion' ) ) {
            customElements.define( 'aiovg-dailymotion', AIOVGDailymotionElement );
        }

        if ( ! customElements.get( 'aiovg-embed' ) ) {
            customElements.define( 'aiovg-embed', AIOVGIframeElement );
        }

        // Update views count for the non-iframe embeds
        const videos = document.querySelectorAll( '.aiovg-player-raw' );
        for ( let i = 0; i < videos.length; i++ ) {
            const elem       = videos[ i ];
            const post_id    = parseInt( elem.dataset.post_id );
            const statistics = parseInt( elem.dataset.statistics );

            if ( statistics ) {
                let formData = new FormData();
                formData.append( 'action', 'aiovg_update_views_count' );
                formData.append( 'post_id', post_id );
                formData.append( 'security', aiovg_embed.ajax_nonce );

                fetch( aiovg_embed.ajax_url, { method: 'POST', body: formData } );
            }
        }

        // Listen to the iframe player events
        window.addEventListener( 'message', function( event ) {
            if ( event.origin != window.location.origin ) {
                return false;
            }        

            if ( ! event.data.hasOwnProperty( 'context' ) || event.data.context != 'iframe' ) {
                return false;
            }

            if ( ! event.data.hasOwnProperty( 'message' ) ) {
                return false;
            }

            if ( event.data.message == 'aiovg-cookie-consent' ) {
                const videos = document.querySelectorAll( '.aiovg-player-element' );
                for ( let i = 0; i < videos.length; i++ ) {
                    videos[ i ].removeCookieConsent();
                }
            }

            if ( event.data.message == 'aiovg-email-captured' ) {
                const videos = document.querySelectorAll( '.aiovg-player-element' );
                for ( let i = 0; i < videos.length; i++ ) {
                    // Consent is site-wide, so it clears everywhere — unlike the email
                    // capture, which is scoped by key. Matches what the producers do for
                    // players in their own window; this covers a capture inside an iframe.
                    videos[ i ].removeCookieConsent();
                    videos[ i ].removeEmailCapture( event.data.capture_key || '' );
                }
            }

            if ( event.data.message == 'aiovg-video-playing' ) {
				const videos = document.querySelectorAll( '.aiovg-player-element' );
				for ( let i = 0; i < videos.length; i++ ) {
					videos[ i ].pause();
				}
			}
        });
    });
    
})();
