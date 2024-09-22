(function( root, factory ) {
  if ( typeof exports === 'object' && typeof module !== 'undefined' ) {
    var videojs = require( 'video.js' );
    module.exports = factory( videojs.default || videojs );
  } else if ( typeof define === 'function' && define.amd ) {
    define( [ 'videojs' ], function( videojs ) {
      return ( root.Vimeo2 = factory( videojs ) );
    });
  } else {
    root.Vimeo2 = factory( root.videojs );
  }
}( this, function( videojs ) {
  'use strict';
 
  var Tech = videojs.getTech( 'Tech' );

  class Vimeo2 extends Tech {

     constructor( options, ready ) {
      super( options, ready );

      this.vimeoState = {
        state: 'UNSTARTED',
        volume: 1,
        muteVolume: 1,
        muted: this.options_.muted ? true : false,
        duration: 0,              
        buffered: 0,
        currentTime: 0,        
        playbackRate: 1
      }

      this.setPoster( options.poster );
      this.setSrc( this.options_.source );    
      
      // Set the vjs-vimeo class to the player
      // Parent is not set yet so we have to wait a tick
      this.setTimeout(function() {
        if ( this.el_ ) {
          this.el_.parentNode.className += ' vjs-vimeo';

          if ( Vimeo2.isApiReady ) {
            this.initVimeoPlayer();
          } else {
            Vimeo2.apiReadyQueue.push( this );
          }
        }
      }.bind(this));
    }

    dispose() {
      if ( this.vimeoPlayer ) {
        // Dispose of the Vimeo Player
        if ( this.vimeoPlayer.unload ) {
          this.vimeoPlayer.unload();
        }

        if ( this.vimeoPlayer.destroy ) {
          this.vimeoPlayer.destroy();
        }
      } else {
        // Vimeo API hasn't finished loading or the player is already disposed
        var index = Vimeo2.apiReadyQueue.indexOf( this );
        if ( index !== -1 ) {
          Vimeo2.apiReadyQueue.splice( index, 1 );
        }
      }

      this.vimeoPlayer = null;

      this.el_.parentNode.className = this.el_.parentNode.className.replace( ' vjs-vimeo', '' );
      this.el_.parentNode.removeChild( this.el_ );

      // Needs to be called after the Vimeo player is destroyed, otherwise there will be a null reference exception
      Tech.prototype.dispose.call( this );
    }

    createEl() {
      var div = document.createElement( 'div' );
      div.setAttribute( 'id', this.options_.techId );
      div.setAttribute( 'class', 'vjs-tech' );

      var divWrapper = document.createElement( 'div' );
      divWrapper.appendChild( div );

      if ( ! this.options_.vimeoControls ) {
        var divBlocker = document.createElement( 'div' );
        divBlocker.setAttribute( 'class', 'vjs-iframe-blocker' );
        divBlocker.setAttribute( 'style', 'position:absolute;top:0;left:0;width:100%;height:100%' );

        divWrapper.appendChild( divBlocker );
      }

      return divWrapper;
    }

    initVimeoPlayer() {
      var playerConfig = {
        byline: false,
        controls: false,
        portrait: false,
        title: false,
        vimeo_logo: false
      };

      // Let the user set any Vimeo parameter
      // https://developer.vimeo.com/player/sdk/embed#table-1
      // To use Vimeo controls, you must use vimeoControls instead
      // To use the loop or autoplay, use the video.js settings

      if ( typeof this.options_.byline !== 'undefined' ) {
        playerConfig.byline = this.options_.byline;
      }

      if ( typeof this.options_.color !== 'undefined' ) {
        // Vimeo is the only API on earth to reject hex color with leading #
        playerConfig.color = this.options_.color.replace( /^#/, '' );
      }

      if ( typeof this.options_.vimeoControls !== 'undefined' ) {
        playerConfig.controls = this.options_.vimeoControls;
      }

      if ( typeof this.options_.muted !== 'undefined' ) {
        playerConfig.muted = this.options_.muted;
      }

      if ( typeof this.options_.loop !== 'undefined' ) {
        playerConfig.loop = this.options_.loop;
      }

      if ( typeof this.options_.playsinline !== 'undefined' ) {
        playerConfig.playsinline = this.options_.playsinline;
      }

      if ( typeof this.options_.portrait !== 'undefined' ) {
        playerConfig.portrait = this.options_.portrait;
      }

      if ( typeof this.options_.title !== 'undefined' ) {
        playerConfig.title = this.options_.title;
      }

      if ( typeof this.options_.vimeo_logo !== 'undefined' ) {
        playerConfig.vimeo_logo = this.options_.vimeo_logo;
      }

      // Allow undocumented options to be passed along via customVars
      if ( typeof this.options_.customVars !== 'undefined' ) {
        var customVars = this.options_.customVars;
        Object.keys( customVars ).forEach(function( key ) {
          playerConfig[ key ] = customVars[ key ];
        });
      }

      this.activeVideoUrl = this.url || null;
      playerConfig.url = this.activeVideoUrl;

      this.vimeoPlayer = new Vimeo.Player( this.options_.techId, playerConfig );      

      this.vimeoPlayer.ready().then(() => {
        this.onPlayerReady();

        var events = [ 'play', 'playing', 'pause', 'ended', 'bufferstart', 'waiting', 'progress', 'timeupdate', 'seeking', 'seeked', 'durationchange', 'volumechange', 'playbackratechange', 'error' ];
        events.forEach(e => {
          this.vimeoPlayer.on( e, ( data ) => this.onPlayerStateChange( e, data ) );
        });
      });
    }

    onPlayerReady() {
      this.isReady_ = true;
      this.triggerReady();

      this.vimeoState.state = 'READY';

      this.trigger( 'loadstart' );
      this.trigger( 'loadedmetadata' );
      this.trigger( 'loadeddata' );
      this.trigger( 'canplay' );
      this.trigger( 'canplaythrough' );
      this.trigger( 'volumechange' );
      this.trigger( 'ratechange' );

      if ( this.playOnReady ) {
        this.play();
      } else if ( this.cueOnReady ) {
        if ( this.activeVideoUrl !== this.url ) {
          this.cueVideoByUrl_( this.url );
          this.activeVideoUrl = this.url;
        }
      }
    }

    onPlayerStateChange( state, data ) {
      switch ( state ) {
        case 'play':
          this.vimeoState.state = 'PLAYING';
          this.trigger( 'play' );
          break;

        case 'playing':
          this.vimeoState.state = 'PLAYING';
          this.trigger( 'playing' );
          break;

        case 'pause':
          this.vimeoState.state = 'PAUSED';
          this.trigger( 'pause' );
          break;

        case 'ended':
          this.vimeoState.state = 'ENDED';
          this.trigger( 'ended' );
          break;

        case 'bufferstart':
        case 'waiting':
          this.vimeoState.state = 'BUFFERING';
          this.trigger( 'play' );
          this.trigger( 'waiting' );
          break;

        case 'progress':
          this.vimeoState.buffered = data.percent;
          this.trigger( 'progress' );
          break;

        case 'timeupdate':
          this.vimeoState.currentTime = data.seconds;
          this.trigger( 'timeupdate' );
          break;

        case 'seeking': 
          this.isSeeking = true;
          this.trigger( 'seeking' );
          break;

        case 'seeked':
          this.isSeeking = false;
          this.trigger( 'seeked' );
          break;      

        case 'durationchange':
          this.vimeoState.duration = data.duration;
          this.trigger( 'durationchange' );
          break;

        case 'volumechange':
          this.trigger( 'volumechange' );
          break;

        case 'playbackratechange':
          this.vimeoState.playbackRate = data.playbackRate;
          this.trigger( 'ratechange' );
          break;

        case 'error':
          // this.trigger( 'error' );
          console.log( data );        
          break;
      }
    }

    error() {
      return { code: null, message: null };
    }

    loadVideoByUrl_( url ) {
      if ( ! this.vimeoPlayer ) {
        return;
      }

      this.vimeoPlayer.loadVideo( url ).then(( url ) => {
        this.play();
      });
    }

    cueVideoByUrl_( url ) {
      if ( ! this.vimeoPlayer ) {
        return;
      }

      this.vimeoPlayer.loadVideo( url );
    }

    src( src ) {
      if ( src ) {
        this.setSrc({ src: src });
      }

      return this.source;
    }

    poster() {
      return this.poster_;
    }

    setPoster( poster ) {
      this.poster_ = poster;
    }    

    setSrc( source ) {
      if ( ! source || ! source.src ) {
        return;
      }

      this.source = source;
      this.url = source.src;

      if ( this.options_.autoplay ) {
        if ( this.isReady_ ) {
          this.play();
        } else {
          this.trigger( 'waiting' );
          this.playOnReady = true;
        }
      } else if ( this.activeVideoUrl !== this.url ) {
        if ( this.isReady_ ) {
          this.cueVideoByUrl_( this.url );
          this.activeVideoUrl = this.url;
        } else {
          this.cueOnReady = true; // for a reference
        }
      }
    }

    autoplay() {
      return this.options_.autoplay;
    }

    setAutoplay( val ) {
      this.options_.autoplay = val;
    }

    loop() {
      return this.options_.loop;
    }

    setLoop( val ) {
      this.options_.loop = val;
    }

    play() {
      if ( ! this.url ) {
        return;
      }    
      
      if ( this.isReady_ ) {
        if ( this.activeVideoUrl === this.url ) {
          this.vimeoPlayer.play().then(() => {
            // Do nothing
          }).catch(( error ) => {
            // Do nothing
          });
        } else {
          this.loadVideoByUrl_( this.url );
          this.activeVideoUrl = this.url;
        }      
      } else {
        this.trigger( 'waiting' );
        this.playOnReady = true;
      }    
    }  

    pause() {
      if ( ! this.vimeoPlayer ) {
        return;
      }

      this.vimeoPlayer.pause().then(() => {
        // Do nothing
      }).catch(( error ) => {
        // Do nothing
      });
    }

    paused() {
      return this.vimeoState.state !== 'PLAYING' && this.vimeoState.state !== 'BUFFERING';
    }  

    currentTime() {
      return this.vimeoState.currentTime;
    }

    setCurrentTime( seconds ) {
      if ( ! this.vimeoPlayer ) {
        return;
      }

      this.vimeoState.currentTime = seconds;

      this.vimeoPlayer.setCurrentTime( seconds ).then(() => {
        // seconds = the actual time that the player seeked to 
        this.vimeoState.currentTime = seconds;
      }).catch(( error ) => {
        // Do nothing
      });    
    }  

    seeking() {
      return this.isSeeking;
    }

    seekable() {
      return videojs.time.createTimeRanges( 0, this.vimeoState.duration );
    }

    playbackRate() {
      return this.vimeoState.playbackRate;
    }

    setPlaybackRate( suggestedRate ) {
      if ( ! this.vimeoPlayer ) {
        return;
      }

      this.vimeoPlayer.setPlaybackRate( suggestedRate ).then(( playbackRate ) => {
        // The playback rate is set
        this.vimeoState.playbackRate = playbackRate;
      }).catch(( error ) => {
        // Do nothing
      });
    }

    duration() {
      return this.vimeoState.duration;
    }

    currentSrc() {
      return this.source && this.source.src;
    }

    ended() {
      return this.vimeoPlayer ? ( this.vimeoState.state === 'ENDED' ) : false;
    } 

    volume() {
      return this.vimeoState.muted ? this.vimeoState.muteVolume : this.vimeoState.volume;
    }

    setVolume( percentAsDecimal ) {
      if ( ! this.vimeoPlayer ) {
        return;
      }
          
      if ( this.vimeoState.state == 'UNSTARTED' && this.vimeoState.muted ) {
        return;
      }
      
      this.vimeoPlayer.setVolume( percentAsDecimal ).then(() => {
        this.vimeoState.volume = percentAsDecimal;
      }).catch(( error ) => {
        // Do nothing
      });
    }

    muted() {
      return this.vimeoPlayer ? this.vimeoState.muted : false;
    }

    setMuted( mute ) {
      if ( ! this.vimeoPlayer ) {
        return;
      }

      if ( mute ) {
        this.vimeoState.muteVolume = this.vimeoState.volume;
        this.setVolume(0);
      } else {
        this.setVolume( this.vimeoState.muteVolume );
      }

      this.vimeoState.muted = mute;
    }

    buffered() {
      return videojs.time.createTimeRanges( 0, this.vimeoState.buffered * this.vimeoState.duration );
    }    

    networkState() {
      if ( ! this.vimeoPlayer ) {
        return 0; // NETWORK_EMPTY
      }

      switch ( this.vimeoState.state ) {
        case 'UNSTARTED':
          return 0; // NETWORK_EMPTY
        case 'BUFFERING':
          return 2; // NETWORK_LOADING
        default:
          return 1; // NETWORK_IDLE
      }
    }

    readyState() {
      if ( ! this.vimeoPlayer ) {
        return 0; // HAVE_NOTHING
      }

      switch ( this.vimeoState.state ) {
        case 'UNSTARTED':
          return 0; // HAVE_NOTHING
        case 'READY':
          return 1; // HAVE_METADATA
        case 'BUFFERING':
          return 2; // HAVE_CURRENT_DATA
        default:
          return 4; // HAVE_ENOUGH_DATA
      }
    }
    
    supportsFullScreen() {
      return document.fullscreenEnabled ||
        document.webkitFullscreenEnabled ||
        document.mozFullScreenEnabled ||
        document.msFullscreenEnabled;
    }

  }

  Vimeo2.prototype.featuresPlaybackRate = true;
  Vimeo2.prototype.featuresProgressEvents = true;
  Vimeo2.prototype.featuresTimeupdateEvents = true;  

  Vimeo2.isSupported = function() {
    return true;
  };

  Vimeo2.canPlaySource = function( e ) {
    return Vimeo2.canPlayType( e.type );
  };

  Vimeo2.canPlayType = function( e ) {
    return ( e === 'video/vimeo' );
  };

  function apiLoaded() {
    Vimeo2.isApiReady = true;

    for ( var i = 0; i < Vimeo2.apiReadyQueue.length; ++i ) {
      Vimeo2.apiReadyQueue[ i ].initVimeoPlayer();
    }
  } 

  function loadScript( src, callback ) {
    var loaded = false;
    var tag = document.createElement( 'script' );
    var firstScriptTag = document.getElementsByTagName( 'script' )[0];

    firstScriptTag.parentNode.insertBefore( tag, firstScriptTag );

    tag.onload = function() {
      if ( ! loaded ) {
        loaded = true;
        callback();
      }
    };

    tag.onreadystatechange = function() {
      if ( ! loaded && ( this.readyState === 'complete' || this.readyState === 'loaded' ) ) {
        loaded = true;
        callback();
      }
    };

    tag.src = src;
  }

  function injectCss() {
    // iframe blocker to catch mouse events
    var css = '.vjs-vimeo iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }';

    var head = document.head || document.getElementsByTagName( 'head' )[0];

    var style = document.createElement( 'style' );
    style.type = 'text/css';

    if ( style.styleSheet ) {
      style.styleSheet.cssText = css;
    } else {
      style.appendChild( document.createTextNode( css ) );
    }

    head.appendChild( style );
  }  

  Vimeo2.apiReadyQueue = [];

  if ( typeof document !== 'undefined' ) {
    loadScript( 'https://player.vimeo.com/api/player.js', apiLoaded );
    injectCss();
  }

  // Older versions of VJS5 doesn't have the registerTech function
  if ( typeof videojs.registerTech !== 'undefined' ) {
    videojs.registerTech( 'Vimeo2', Vimeo2 );
  } else {
    videojs.registerComponent( 'Vimeo2', Vimeo2 );
  }
}));