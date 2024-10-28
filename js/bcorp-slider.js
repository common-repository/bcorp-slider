jQuery(function($){
	"use strict";

	var _CaptionTransitions = [];
	$(".bcorp-slider [data-u='caption']").each(function(){
		var t_name = $(this).attr('data-t');
		var t_transition =  eval('(' + $(this).attr('data-transition-t')+ ')');
		if (t_name != undefined && t_transition != undefined) { _CaptionTransitions[t_name] = t_transition; }
		var t_name2 = $(this).attr('data-t2');
		var t_transition2 =  eval('(' + $(this).attr('data-transition-t2')+ ')');
		if (t_name2 != undefined && t_transition2 != undefined) { _CaptionTransitions[t_name2] = t_transition2; }
	});

	$(".bcorp-carousel-content [data-u='caption']").each(function(){
		var t_name = $(this).attr('data-t');
		var t_transition =  eval('(' + $(this).attr('data-transition-t')+ ')');
		if (t_name != undefined && t_transition != undefined) { _CaptionTransitions[t_name] = t_transition; }
		var t_name2 = $(this).attr('data-t2');
		var t_transition2 =  eval('(' + $(this).attr('data-transition-t2')+ ')');
		if (t_name2 != undefined && t_transition2 != undefined) { _CaptionTransitions[t_name2] = t_transition2; }
	});

	$(window).bind("load", scaleSlider);
	$(window).bind("resize", scaleSlider);
	$(window).bind("orientationchange", scaleSlider);

	function scaleSlider() {
		sliders.forEach(function(slider,index){
			if (slider.$Elmt.parentNode.clientWidth != 0) {
				if ($(slider.$Elmt).hasClass('bcorp-fullscreen')) {
					var aspect_ratio = slider.$OriginalWidth()/slider.$OriginalHeight();
					var current_aspect_ratio = slider.$Elmt.parentNode.clientWidth/slider.$Elmt.parentNode.clientHeight;
					if (aspect_ratio < current_aspect_ratio) {
							slider.$ScaleWidth(slider.$Elmt.parentNode.clientWidth);
							$(slider.$Elmt).css ('left','0');
					} else {
							slider.$ScaleHeight(slider.$Elmt.parentNode.clientHeight);
							$(slider.$Elmt).css ('left',(slider.$Elmt.parentNode.clientWidth - slider.$Elmt.clientWidth ) / 2+'px');
							var arrowindent = (-(slider.$Elmt.parentNode.clientWidth - slider.$Elmt.clientWidth ) / 2+50)*slider.$OriginalWidth()/slider.$ScaleWidth();
							$(slider.$Elmt).find('.jssora2l').css('left',arrowindent+'px');
							$(slider.$Elmt).find('.jssora2r').css('right',arrowindent+'px');
					}
					var arrowtop = (slider.$Elmt.parentNode.clientHeight*slider.$OriginalHeight()/slider.$ScaleHeight()-55)/2;
					$(slider.$Elmt).find('.jssora2l').css('top',arrowtop+'px');
					$(slider.$Elmt).find('.jssora2r').css('top',arrowtop+'px');
				} else {
		      var minHeight = $(slider.$Elmt).attr('data-min-height');
					var maxHeight = $(slider.$Elmt).attr('data-max-height');
		      var newHeight = slider.$OriginalHeight() / slider.$OriginalWidth() * slider.$Elmt.parentNode.clientWidth;
		      if (minHeight!='undefined' && newHeight < minHeight) {
		        slider.$ScaleHeight(minHeight);
						$(slider.$Elmt).css ('left',(slider.$Elmt.parentNode.clientWidth - slider.$Elmt.clientWidth ) / 2+'px');
					} else {
						slider.$ScaleWidth(slider.$Elmt.parentNode.clientWidth);
						$(slider.$Elmt).css ('left','0');
					}
				}
			} else {
				slider.$Pause();
				sliders.splice(index, 1);
			}
		});

		carousels.forEach(function(carousel,index){
			carousel.$ScaleWidth(carousel.$Elmt.parentNode.clientWidth);
			var minHeight = parseInt($(carousel.$Elmt).attr('data-min-height'));
			var maxHeight = $(carousel.$Elmt).attr('data-max-height');
			var newHeight = carousel.$OriginalHeight() / carousel.$OriginalWidth() * carousel.$Elmt.parentNode.clientWidth;
			if (minHeight!='undefined' && newHeight < minHeight) {
				carousel.$ScaleHeight(minHeight);
				$(carousel.$Elmt).css ('left',(carousel.$Elmt.parentNode.clientWidth - carousel.$Elmt.clientWidth ) / 2+'px');
			} else {
				carousel.$ScaleWidth(carousel.$Elmt.parentNode.clientWidth);
				$(carousel.$Elmt).css ('left','0');
			}
		});
	}

	function startSlider(slider) {
		var transitions = $(slider).attr('data-transitions').split('|');
		for (var x in transitions) {
			transitions[x] = eval('(' + transitions[x]+ ')');
		}
		var options = {
			$AutoPlay: true,
			$FillMode: 0,                             //[Optional] The way to fill image in slide, 0 stretch, 1 contain (keep aspect ratio and put all inside slide), 2 cover (keep aspect ratio and cover whole slide), 4 actual size, 5 contain for large image, actual size for small image, default value is 0
			$SlideshowOptions: {                      //[Optional] Options to specify and enable slideshow or not
					$Class: $JssorSlideshowRunner$,       //[Required] Class to create instance of slideshow
					$Transitions: transitions,            //[Required] An array of slideshow transitions to play slideshow
					$TransitionsOrder: 0,                 //[Optional] The way to choose transition to play slide, 1 Sequence, 0 Random
					$ShowLink: true                       //[Optional] Whether to bring slide link on top of the slider when slideshow is running, default value is false
			},

			$CaptionSliderOptions: {                            //[Optional] Options which specifies how to animate caption
					$Class: $JssorCaptionSlider$,                   //[Required] Class to create instance to animate caption
					$CaptionTransitions: _CaptionTransitions,       //[Required] An array of caption transitions to play caption, see caption transition section at jssor slideshow transition builder
					$PlayInMode: 1,                                 //[Optional] 0 None (no play), 1 Chain (goes after main slide), 3 Chain Flatten (goes after main slide and flatten all caption animations), default value is 1
					$PlayOutMode: 3                                 //[Optional] 0 None (no play), 1 Chain (goes before main slide), 3 Chain Flatten (goes before main slide and flatten all caption animations), default value is 1
			},

			$ArrowNavigatorOptions: {                       //[Optional] Options to specify and enable arrow navigator or not
					$Class: $JssorArrowNavigator$,              //[Requried] Class to create arrow navigator instance
					$ChanceToShow: 2,                               //[Required] 0 Never, 1 Mouse Over, 2 Always
					$AutoCenter: 2,                                 //[Optional] Auto center arrows in parent container, 0 No, 1 Horizontal, 2 Vertical, 3 Both, default value is 0
					$Steps: 1                                       //[Optional] Steps to go for each navigation request, default value is 1
			},
			$BulletNavigatorOptions: {                                //[Optional] Options to specify and enable navigator or not
					$Class: $JssorBulletNavigator$,                       //[Required] Class to create navigator instance
					$ChanceToShow: 2,                               //[Required] 0 Never, 1 Mouse Over, 2 Always
					$AutoCenter: 1,                                 //[Optional] Auto center navigator in parent container, 0 None, 1 Horizontal, 2 Vertical, 3 Both, default value is 0
					$Steps: 1,                                      //[Optional] Steps to go for each navigation request, default value is 1
					$Lanes: 1,                                      //[Optional] Specify lanes to arrange items, default value is 1
					$SpacingX: 10,                                  //[Optional] Horizontal space between each item in pixel, default value is 0
					$SpacingY: 10,                                  //[Optional] Vertical space between each item in pixel, default value is 0
					$Orientation: 1                                 //[Optional] The orientation of the navigator, 1 horizontal, 2 vertical, default value is 1
			}
		};
		sliders.push(new $JssorSlider$(slider, options));
	}


	function startCarousel(carousel) {
		var spacing = $(carousel).attr('data-spacing');
		if (!spacing) spacing = 4 ;
		var columns = $(carousel).attr('data-columns');
		var width = 1200 + (columns-2)*spacing;
		var slidewidth = 1200 / columns;
		if ($(carousel).attr('data-mobile')) {
			if (columns>1) {
				columns = 2; width = 403; slidewidth=200;
			} else {
				columns = 1; width = 400; slidewidth=400;
			}
		}
		var steps = $(carousel).attr('data-steps');
		if (!steps) steps =1 ;
		var offset = $(carousel).attr('data-offset')*slidewidth;
		if (!offset) offset = 0 ;
		$(carousel).css('width',width+'px').css('height','200px').find('.bcorp-carousel-slides').css('width',width+'px').css('height','50px');
		var options = {
				$FillMode: 0,                 //1                      //[Optional] The way to fill image in slide, 0 stretch, 1 contain (keep aspect ratio and put all inside slide), 2 cover (keep aspect ratio and cover whole slide), 4 actual size, 5 contain for large image, actual size for small image, default value is 0
				$AutoPlay: true,                                    //[Optional] Whether to auto play, to enable slideshow, this option must be set to true, default value is false
				$AutoPlaySteps: 1,                                  //[Optional] Steps to go for each navigation request (this options applys only when slideshow disabled), the default value is 1
				$AutoPlayInterval: 4000,                            //[Optional] Interval (in milliseconds) to go for next slide since the previous stopped if the slider is auto playing, default value is 3000
				$PauseOnHover: 1,                               //[Optional] Whether to pause when mouse over if a slider is auto playing, 0 no pause, 1 pause for desktop, 2 pause for touch device, 3 pause for desktop and touch device, 4 freeze for desktop, 8 freeze for touch device, 12 freeze for desktop and touch device, default value is 1

				$ArrowKeyNavigation: true,   			            //[Optional] Allows keyboard (arrow key) navigation or not, default value is false
				$SlideDuration: 160,                                //[Optional] Specifies default duration (swipe) for slide in milliseconds, default value is 500
				$MinDragOffsetToSlide: 20,                          //[Optional] Minimum drag offset to trigger slide , default value is 20
				$SlideWidth: slidewidth,                                   //[Optional] Width of every slide in pixels, default value is width of 'slides' container

				$SlideSpacing: spacing, 					                //[Optional] Space between each slide in pixels, default value is 0
				$Cols: columns,                                  //[Optional] Number of pieces to display (the slideshow would be disabled if the value is set to greater than 1), the default value is 1
				$UISearchMode: 1,                                   //[Optional] The way (0 parellel, 1 recursive, default value is 1) to search UI components (slides container, loading screen, navigator container, arrow navigator container, thumbnail navigator container etc).
				$PlayOrientation: 1,                                //[Optional] Orientation to play slide (for auto play, navigation), 1 horizental, 2 vertical, 5 horizental reverse, 6 vertical reverse, default value is 1
				$DragOrientation: 1,                                //[Optional] Orientation to drag slide, 0 no drag, 1 horizental, 2 vertical, 3 either, default value is 1 (Note that the $DragOrientation should be the same as $PlayOrientation when $DisplayPieces is greater than 1, or parking position is not 0)
        $ParkingPosition: offset,                                //The offset position to park slide (this options applys only when slideshow disabled).
				$CaptionSliderOptions: {                            //[Optional] Options which specifies how to animate caption
						$Class: $JssorCaptionSlider$,                   //[Required] Class to create instance to animate caption
						$CaptionTransitions: _CaptionTransitions,       //[Required] An array of caption transitions to play caption, see caption transition section at jssor slideshow transition builder
						$PlayInMode: 1,                                 //[Optional] 0 None (no play), 1 Chain (goes after main slide), 3 Chain Flatten (goes after main slide and flatten all caption animations), default value is 1
						$PlayOutMode: 3                                 //[Optional] 0 None (no play), 1 Chain (goes before main slide), 3 Chain Flatten (goes before main slide and flatten all caption animations), default value is 1
				},
				$ArrowNavigatorOptions: {                       //[Optional] Options to specify and enable arrow navigator or not
						$Class: $JssorArrowNavigator$,              //[Requried] Class to create arrow navigator instance
						$ChanceToShow: 2,                               //[Required] 0 Never, 1 Mouse Over, 2 Always
						$Steps: 1                                       //[Optional] Steps to go for each navigation request, default value is 1
				},
		};
		var newcarousel = new $JssorSlider$(carousel, options);
		var newheight = 0;
		$(carousel).find('.bcorp-carousel-content-inner').each(function(){ newheight = Math.max(newheight, $(this).outerHeight()); });
		$(carousel).css('height',newheight+'px').find('.bcorp-carousel-slides').css('height',newheight+'px');
		$(carousel).find('.bcorp-carousel-content').each(function(){
			$(this).css('height',newheight+'px');
		});
		newcarousel = new $JssorSlider$(carousel, options);
		carousels.push(newcarousel);
		return newcarousel;
	}

	var sliders = [];
	var carousels = [];

	$('body').imagesLoaded(function(){
		$('.bcorp-slider').each(function(){ startSlider(this); });
		$('.bcorp-carousel').each(function(){ startCarousel(this); });
		scaleSlider();
	});
});
