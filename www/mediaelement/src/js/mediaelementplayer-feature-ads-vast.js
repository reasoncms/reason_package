/**
 * VAST Ads Plugin
 *
 * Sponsored by Minoto Video
 */
(function($) {

	// Feature configuration
	$.extend(mejs.MepDefaults, {
		/**
		 * URL to vast data (http://minotovideo.com/sites/minotovideo.com/files/upload/eday_vast_tag.xml)
		 * @type {String}
		 */
		vastAdTagUrl: ''
	});

	$.extend(MediaElementPlayer.prototype, {

		/**
		 * @type {Boolean}
		 */
		vastAdTagIsLoading: false,
		/**
		 * @type {Boolean}
		 */
		vastAdTagIsLoaded: false,
		/**
		 * @type {Boolean}
		 */
		vastStartedPlaying: false,
		/**
		 * @type {Array}
		 */
		vastAdTags: [],

		/**
		 * Feature constructor.
		 *
		 * Always has to be prefixed with `build` and the name that will be used in MepDefaults.features list
		 * @param {MediaElementPlayer} player
		 * @param {$} controls
		 * @param {$} layers
		 * @param {HTMLElement} media
		 */
		buildvast: function(player, controls, layers, media) {

			var t = this;	
			
			// begin loading
			if (t.options.vastAdTagUrl !== '') {
				t.vastLoadAdTagInfo();
			}
		
			// make sure the preroll ad system is ready (it will ensure it can't be called twice)
			t.buildads(player, controls, layers, media);	

			t.vastSetupEvents();					
		},
	
		vastSetupEvents: function() {
			var t = this;
			
			
			// START: preroll
			t.container.on('mejsprerollstarted', function() {			

				if (t.vastAdTags.length > 0) {
				
					var adTag = t.vastAdTags[0];
					
					// always fire this event
					if (adTag.trackingEvents.start) {
						t.adsLoadUrl(adTag.trackingEvents.start);
					}

					// only do impressions once
					if (!adTag.shown && adTag.impressions.length > 0) {
						
						for (var i=0, il=adTag.impressions.length; i<il; i++) {
							t.adsLoadUrl(adTag.impressions[i]);
						}
					}
					
					adTag.shown = true;
				}
				
			});
			
			// END: preroll
			t.container.on('mejsprerollended', function() {			

				if (t.vastAdTags.length > 0 && t.options.indexPreroll < t.vastAdTags.length &&
                    			t.vastAdTags[t.options.indexPreroll].trackingEvents.complete) {
					t.adsLoadUrl(t.vastAdTags[t.options.indexPreroll].trackingEvents.complete);
				}
				
			});			
			
		},

		/**
		 *
		 * @param {String} url
		 */
		vastSetAdTagUrl: function(url) {
		
			var t = this;		
		
			// set and reset
			t.options.vastAdTagUrl = url;
			t.options.indexPreroll = 0;
			t.vastAdTagIsLoaded = false;
			t.vastAdTags = [];
		},

		/**
		 *
		 */
		vastLoadAdTagInfo: function() {
			var t = this;
			
			// set this to stop playback
			t.adsDataIsLoading = true;
			t.vastAdTagIsLoading = true;
			
			// try straight load first
			t.loadAdTagInfoDirect();
		},

		/**
		 *
		 */
		loadAdTagInfoDirect: function() {
			var t = this;
			
			$.ajax({
				url: t.options.vastAdTagUrl,
				crossDomain: true,
				success: function(data) {
					t.vastParseVastData(data);
				},
				error: function(err) {
					console.log('vast3:direct:error', err);

					// fallback to Yahoo proxy
					t.loadAdTagInfoProxy();
				}	
			});
		},

		/**
		 *
		 */
		loadAdTagInfoProxy: function() {
			var t = this,
				protocol = location.protocol,
				hostname = location.hostname,
				query = 'select * from xml where url="' + encodeURI(t.options.vastAdTagUrl) +'"',
				yahooUrl = 'http' + (/^https/.test(protocol)?'s':'') + '://query.yahooapis.com/v1/public/yql?format=xml&q=' + query;


			
			$.ajax({
				url: yahooUrl,
				crossDomain: true,
				success: function(data) {
					t.vastParseVastData(data);
				},
				error: function(err) {
					console.log('vast:proxy:yahoo:error', err);
				}	
			});
		},

		/**
		 *
		 * @param {jQuery} data
		 */
		vastParseVastData: function(data) {
			
			var t = this;
			
			
			// clear out data
			t.vastAdTags = [];
			t.options.indexPreroll = 0;
			
			$(data).find('Ad').each(function(index, node) {
					
				var 
					adNode = $(node),
					
					adTag = {					
						id: adNode.attr('id'),
						title: $.trim( adNode.find('AdTitle').text() ),
						description: $.trim( adNode.find('Description').text() ),
						impressions: [],
						clickThrough: $.trim( adNode.find('ClickThrough').text() ),
						mediaFiles: [],
						trackingEvents: {},
						
						// internal tracking if it's been used
						shown: false
					};	
					
				t.vastAdTags.push(adTag);				
				
					
				// parse all needed nodes
				adNode.find('Impression').each(function() {
					adTag.impressions.push( $.trim( $(this).text() ) );
				});	
				
				adNode.find('Tracking').each(function(index, node) {
					var trackingEvent = $(node);
				
					adTag.trackingEvents[trackingEvent.attr('event')] = $.trim( trackingEvent.text() );
					
				});		
				
		
				adNode.find('MediaFile').each(function(index, node) {
					var mediaFile = $(node),
						type = mediaFile.attr('type');
						
					if (t.media.canPlayType(type).toString().replace(/no/,'').replace(/false/,'') !== '') {
					
						adTag.mediaFiles.push({
							id: mediaFile.attr('id'),
							delivery: mediaFile.attr('delivery'),
							type: mediaFile.attr('type'),
							bitrate: mediaFile.attr('bitrate'),
							width: mediaFile.attr('width'),
							height: mediaFile.attr('height'),
							url:  $.trim( mediaFile.text() )
						} );
					}
				});		
				
			});
		
			// DONE
			t.vastLoaded();
		},

		/**
		 *
		 */
		vastLoaded: function() {
			var t = this;
			
			t.vastAdTagIsLoaded = true;
			t.vastAdTagIsLoading = false;
			t.adsDataIsLoading = false;
					
			t.vastStartPreroll();
		},

		/**
		 *
		 */
		vastStartPreroll: function() {
			var t = this;
				
			// if we have a media URL, then send it up to the ads plugin as a preroll
			// load up the vast ads to be played before the selected media.
			// Note: multiple preroll ads are supported.
			var i = 0;
			while (i < t.vastAdTags.length) {
				t.options.adsPrerollMediaUrl[i] = t.vastAdTags[i].mediaFiles[0].url;
				t.options.adsPrerollAdUrl[i] = t.vastAdTags[i].clickThrough;
				i++;
			}
			t.adsStartPreroll();
			
		}
		
	});

})(mejs.$);
