(function( $ ) {
    'use strict';

    class AIOVGLikeButtonElement extends HTMLElement {

        /**
         * Element created.
         */
        constructor() {
            super(); 
            
            // Set references to the DOM elements used by the component
            this._likeButtonEl    = null;
            this._dislikeButtonEl = null;

            // Set references to the private properties used by the component
            this._isRendered = false;  
            this._isLoading  = false; 
            this._options    = {};    
            this._params     = {};

            // Bind the event handlers to ensure the reference remains stable
            this._toggleLikes = this._toggleLikes.bind( this );
            this._toggleDislikes = this._toggleDislikes.bind( this );
        }

        /**
         * Browser calls this method when the element is added to the document.
         * (can be called many times if an element is repeatedly added/removed)
         */
        connectedCallback() {    
            if ( this._isRendered ) {
                return false; 
            }           
            
            if ( this.postId == 0 ) {
                return false;
            }

            this._options = window.aiovg_likes;

            if ( ! this.hasLikeButton && ! this.hasDislikeButton ) { 
                return false; 
            }

            this._isRendered = true;       

            this._params = {
                'likes': parseInt( this.getAttribute( 'likes' ) || 0 ),                
                'dislikes': parseInt( this.getAttribute( 'dislikes' ) || 0 ),
                'liked': this.hasAttribute( 'liked' ),
                'disliked': this.hasAttribute( 'disliked' )
            };

            // Likes Button
            if ( this.hasLikeButton ) {           
                this._likeButtonEl = document.createElement( 'button' );
                this._likeButtonEl.type = 'button';
                this._likeButtonEl.className = 'aiovg-button-like';                            

                this._updateLikeButton(); 

                this.appendChild( this._likeButtonEl );                

                this._likeButtonEl.addEventListener( 'click', this._toggleLikes ); 
            }

            // Dislikes Button
            if ( this.hasDislikeButton ) {           
                this._dislikeButtonEl = document.createElement( 'button' );
                this._dislikeButtonEl.type = 'button';
                this._dislikeButtonEl.className = 'aiovg-button-dislike'; 
                
                this._updateDislikeButton();

                this.appendChild( this._dislikeButtonEl );

                this._dislikeButtonEl.addEventListener( 'click', this._toggleDislikes ); 
            }

            this._load();
        }

        /**
         * Browser calls this method when the element is removed from the document.
         * (can be called many times if an element is repeatedly added/removed)
         */
        disconnectedCallback() {
            // TODO
        }

        /**
         * Define getters and setters for attributes.
         */     

        get postId() {
            return parseInt( this.getAttribute( 'post_id' ) || 0 );
        }

        get hasLikeButton() {
            return parseInt( this._options.show_like_button ) || 0;
        }

        get hasDislikeButton() {
            return parseInt( this._options.show_dislike_button ) || 0;
        }

        get userId() {
            return parseInt( this._options.user_id );
        }

        get loginRequired() {
            return parseInt( this._options.login_required_to_vote ) || 0;
        }

        get isLoaded() {
            return this.hasAttribute( 'loaded' );
        }

        set isLoaded( value ) {
            return this.setAttribute( 'loaded', value );
        }

        /**
         * Define private methods.
         */     

        _load() {
            if ( this.isLoaded ) {
                return false;
            }

            let data = {
                'action': 'aiovg_get_likes_dislikes_info',
                'user_id': this.userId,
                'post_id': this.postId,
                'security': this._options.ajax_nonce
            };

            this._fetch( data, ( response ) => {
                this.isLoaded = true;

                if ( response.status == 'success' ) {
                    this._params = response;

                    this._updateLikeButton();
                    this._updateDislikeButton();
                }    
            }); 
        }

        _toggleLikes() {   
            if ( this.loginRequired && this.userId === 0 ) {
                alert( this._options.i18n.alert_login_required );
                return false;
            } 
            
            if ( this._isLoading ) {
                return false;
            }

            // Store
            let data = {
                'action': 'aiovg_toggle_likes',                   
                'user_id': this.userId,
                'post_id': this.postId,
                'context': ( this._params.liked ? 'remove_from_likes' : 'add_to_likes' ),
                'security': this._options.ajax_nonce
            };

            this._isLoading = true;
            this._likeButtonEl.querySelector( 'svg' ).classList.add( 'aiovg-animate-rotate' );
            
            this._fetch( data, ( response ) => {
                this._isLoading = false;

                if ( response.status == 'success' ) {
                    this._params = response;           
                }
                
                this._updateLikeButton();
                this._updateDislikeButton();
            });        
        }

        _toggleDislikes() {
            if ( this.loginRequired && this.userId === 0 ) {
                alert( this._options.i18n.alert_login_required );
                return false;
            }

            if ( this._isLoading ) {
                return false;
            }

            // Store
            let data = {
                'action': 'aiovg_toggle_dislikes',                   
                'user_id': this.userId,
                'post_id': this.postId,
                'context': ( this._params.disliked ? 'remove_from_dislikes' : 'add_to_dislikes' ),
                'security': this._options.ajax_nonce
            };

            this._isLoading = true;
            this._dislikeButtonEl.querySelector( 'svg' ).classList.add( 'aiovg-animate-rotate' );
            
            this._fetch( data, ( response ) => {
                this._isLoading = false;

                if ( response.status == 'success' ) {                
                    this._params = response;                
                }    
                
                this._updateLikeButton();
                this._updateDislikeButton();
            });     
        }

        _updateLikeButton() {
            if ( ! this._likeButtonEl ) {
                return false;
            }

            let html = '';

            if ( this._params.liked ) {
                html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="aiovg-flex-shrink-0">' + 
                    '<path d="M7.493 18.5c-.425 0-.82-.236-.975-.632A7.48 7.48 0 0 1 6 15.125c0-1.75.599-3.358 1.602-4.634.151-.192.373-.309.6-.397.473-.183.89-.514 1.212-.924a9.042 9.042 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75A.75.75 0 0 1 15 2a2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H14.23c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23h-.777ZM2.331 10.727a11.969 11.969 0 0 0-.831 4.398 12 12 0 0 0 .52 3.507C2.28 19.482 3.105 20 3.994 20H4.9c.445 0 .72-.498.523-.898a8.963 8.963 0 0 1-.924-3.977c0-1.708.476-3.305 1.302-4.666.245-.403-.028-.959-.5-.959H4.25c-.832 0-1.612.453-1.918 1.227Z" />' + 
                '</svg>';
            } else {
                html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">' + 
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />' + 
                '</svg>';
            }

            html += '<span class="aiovg-likes aiovg-flex aiovg-gap-1 aiovg-items-center">';
            html += '<span class="aiovg-likes-count">' + this._params.likes + '</span>';
            html += '<span class="aiovg-likes-label">' + this._options.i18n.likes + '</span>';
            html += '</span>';

            this._likeButtonEl.innerHTML = html;
        }

        _updateDislikeButton() {
            if ( ! this._dislikeButtonEl ) {
                return false;
            }

            let html = '';

            if ( this._params.disliked ) {
                html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="aiovg-flex-shrink-0">' + 
                    '<path d="M15.73 5.5h1.035A7.465 7.465 0 0 1 18 9.625a7.465 7.465 0 0 1-1.235 4.125h-.148c-.806 0-1.534.446-2.031 1.08a9.04 9.04 0 0 1-2.861 2.4c-.723.384-1.35.956-1.653 1.715a4.499 4.499 0 0 0-.322 1.672v.633A.75.75 0 0 1 9 22a2.25 2.25 0 0 1-2.25-2.25c0-1.152.26-2.243.723-3.218.266-.558-.107-1.282-.725-1.282H3.622c-1.026 0-1.945-.694-2.054-1.715A12.137 12.137 0 0 1 1.5 12.25c0-2.848.992-5.464 2.649-7.521C4.537 4.247 5.136 4 5.754 4H9.77a4.5 4.5 0 0 1 1.423.23l3.114 1.04a4.5 4.5 0 0 0 1.423.23ZM21.669 14.023c.536-1.362.831-2.845.831-4.398 0-1.22-.182-2.398-.52-3.507-.26-.85-1.084-1.368-1.973-1.368H19.1c-.445 0-.72.498-.523.898.591 1.2.924 2.55.924 3.977a8.958 8.958 0 0 1-1.302 4.666c-.245.403.028.959.5.959h1.053c.832 0 1.612-.453 1.918-1.227Z" />' + 
                '</svg>';
            } else {
                html += '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">' + 
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M7.498 15.25H4.372c-1.026 0-1.945-.694-2.054-1.715a12.137 12.137 0 0 1-.068-1.285c0-2.848.992-5.464 2.649-7.521C5.287 4.247 5.886 4 6.504 4h4.016a4.5 4.5 0 0 1 1.423.23l3.114 1.04a4.5 4.5 0 0 0 1.423.23h1.294M7.498 15.25c.618 0 .991.724.725 1.282A7.471 7.471 0 0 0 7.5 19.75 2.25 2.25 0 0 0 9.75 22a.75.75 0 0 0 .75-.75v-.633c0-.573.11-1.14.322-1.672.304-.76.93-1.33 1.653-1.715a9.04 9.04 0 0 0 2.86-2.4c.498-.634 1.226-1.08 2.032-1.08h.384m-10.253 1.5H9.7m8.075-9.75c.01.05.027.1.05.148.593 1.2.925 2.55.925 3.977 0 1.487-.36 2.89-.999 4.125m.023-8.25c-.076-.365.183-.75.575-.75h.908c.889 0 1.713.518 1.972 1.368.339 1.11.521 2.287.521 3.507 0 1.553-.295 3.036-.831 4.398-.306.774-1.086 1.227-1.918 1.227h-1.053c-.472 0-.745-.556-.5-.96a8.95 8.95 0 0 0 .303-.54" />' + 
                '</svg>';
            }

            html += '<span class="aiovg-dislikes aiovg-flex aiovg-gap-1 aiovg-items-center">';
            html += '<span class="aiovg-dislikes-count">' + this._params.dislikes + '</span>';
            html += '<span class="aiovg-dislikes-label">' + this._options.i18n.dislikes + '</span>';
            html += '</span>';

            this._dislikeButtonEl.innerHTML = html;
        }  
        
        _fetch( data, callback ) {
            $.post( this._options.ajax_url, data, callback, 'json' ); 						
        }

    }

    /**
	 * Called when the page has loaded.
	 */
	$(function() {		
		// Register custom element
        if ( ! customElements.get( 'aiovg-like-button' ) ) {
		    customElements.define( 'aiovg-like-button', AIOVGLikeButtonElement );
        }
	});
    
})( jQuery );