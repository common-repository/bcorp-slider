<?php
/*
Plugin Name: BCorp Slider
Plugin URI: http://shortcodes.bcorp.com
Description: Slider Shortcodes for integration with BCorp Shortcodes & BCorp Visual Editor
Version: 0.1
Author: Tim Brattberg
Author URI: http://bcorp.com
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
*/

require_once(plugin_dir_path( __FILE__ ). 'bcorp_slider_data.php' );

if(function_exists('bcorp_shortcodes_init')){
  add_action('bcorp_shortcodes_extra', 'bcorp_slider_init');
  function bcorp_slider_init() {
    new BCorp_Slider_Data();
    $GLOBALS['bcorp_slider'] = new BCorp_Slider();
  }
} else {
  if (is_admin()) {
    add_action('media_buttons', 'add_bcorp_slider_button');
    add_action('admin_enqueue_scripts','bcorp_slider_admin_enqueue_scripts');
  }
}

function bcorp_slider_admin_enqueue_scripts() {
  wp_enqueue_style('bcorp_slider_admin_css',plugins_url( 'css/bcorp-slider-admin.css' , __FILE__ ));
  wp_enqueue_script('bcorp_slider_admin_js',plugins_url('js/bcorp-slider-admin.js', __FILE__ ),'','',true);
  $plugins = array_keys(get_plugins());
  $myplugin = 'bcorp-shortcodes';
  $installed=false;
  foreach($plugins as $plugin) if(strpos($plugin, $myplugin.'/') === 0) { $installed = true; break; }
  if ($installed) {
    $url = wp_nonce_url( self_admin_url('plugins.php?action=activate&plugin='.$plugin), 'activate-plugin_'.$plugin);
  } else {
    $plugin = 'bcorp-shortcodes';
    $plugin_name = 'BCorp Shortcodes';
    $url = wp_nonce_url(
      add_query_arg(
        array(
          'page'          => 'bcorp_shortcodes_plugin_activation',
          'plugin'        => $plugin,
          'plugin_name'   => $plugin_name,
          'plugin_source' => !empty($source) ? urlencode($source) : false,
          'bcorp-shortcodes-install' => 'install-plugin',
        ),
        admin_url( 'plugins.php' )
      ),
      'bcorp-shortcodes-install'
    );
  }
  wp_localize_script("bcorp_slider_admin_js","bcorp_installer", array('url' => $url,'installed' => $installed));
}

function add_bcorp_slider_button() {
  echo '<a href="#" id="bcorp-slider-button" class="button">BCorp Slider</a>';
}

function bcorp_slider_plugin_activation_page(){
	if( !isset( $_GET[  'bcorp-shortcodes-install' ] ) ) return;

	add_plugins_page(
		__('Install BCorp Shortcodes Plugin', 'bcorp-slider'),
		__('Install BCorp Shortcodes Plugin', 'bcorp-slider'),
		'install_plugins',
		'bcorp_shortcodes_plugin_activation',
		'bcorp_slider_shortcodes_installer_page'
	);
}
add_action('admin_menu', 'bcorp_slider_plugin_activation_page');


function bcorp_slider_shortcodes_installer_page(){
	?>
	<div class="wrap">
		<?php bcorp_slider_shortcodes_install() ?>
	</div>
	<?php
}

function bcorp_slider_shortcodes_install(){
	if (isset($_GET[sanitize_key('plugin')]) && (isset($_GET[sanitize_key('bcorp-shortcodes-install')]) && 'install-plugin' == $_GET[sanitize_key('bcorp-shortcodes-install')]) && current_user_can('install_plugins')) {
		check_admin_referer( 'bcorp-shortcodes-install' );
		$plugin_name = $_GET['plugin_name'];
		$plugin_slug = $_GET['plugin'];
		if(!empty($_GET['plugin_source'])) $plugin_source = $_GET['plugin_source']; else $plugin_source = false;
		$url = wp_nonce_url(
			add_query_arg(
				array(
					'page'          => 'bcorp_shortcodes_plugin_activation',
					'plugin'        => $plugin_slug,
					'plugin_name'   => $plugin_name,
					'plugin_source' => $plugin_source,
					'bcorp-shortcodes-install' => 'install-plugin',
				),
				admin_url( 'themes.php' )
			),
			'bcorp-shortcodes-install'
		);
		$fields = array( sanitize_key( 'bcorp-shortcodes-install' ) );
		if (false === ($creds=request_filesystem_credentials($url,'',false,false,$fields))) return true;
		if (!WP_Filesystem($creds)) {
			request_filesystem_credentials($url,'', true,false,$fields);
			return true;
		}
		require_once ABSPATH.'wp-admin/includes/plugin-install.php';
		require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
		$title = sprintf( __('Installing %s', 'bcorp-slider'), $plugin_name );
		$url = add_query_arg( array('action' => 'install-plugin','plugin' => urlencode($plugin_slug)),'update.php');
		if (isset($_GET['from'])) $url .= add_query_arg('from',urlencode(stripslashes($_GET['from'])),$url);
		$nonce = 'install-plugin_' . $plugin_slug;
		$source = !empty( $plugin_source ) ? $plugin_source : 'http://downloads.wordpress.org/plugin/'.urlencode($plugin_slug).'.zip';
		$upgrader = new Plugin_Upgrader($skin = new Plugin_Installer_Skin(compact('type','title','url','nonce','plugin','api')));
		$upgrader->install($source);
		wp_cache_flush();
	}
}

class BCorp_Slider {
  public function __construct ()
  {
    if (!is_admin()) $this->setup();
  }

  private function setup () {
    add_action('wp_enqueue_scripts', array(&$this,'enqueue_scripts'));
  }

  function enqueue_scripts() {
    wp_enqueue_style('bcorp_slider_css',plugins_url( 'css/bcorp-slider.css' , __FILE__ ));
    wp_enqueue_script('bcorp_slider_js',plugins_url('js/bcorp-slider.js', __FILE__ ),'','',true);
    wp_enqueue_script('jssor', plugins_url('/js/jssor.slider.mini.js', __FILE__ ),'','',true);
  }

  function get_setting($setting1,$setting2){
  	global $bcorp_settings;
  	$setting=json_decode($bcorp_settings[$setting1], true);
  	return $setting[$setting2];
  }

  function fullwidth_start($size) {
    global $bcorp_section_id;
    $bcorp_section_id++;
    return '</div></div></section><section id="bcorp-section-'.$bcorp_section_id.'" class="bcorp-'.$size.' bcorp-color-main">';
  }

  function fullwidth_end() {
    return '</section><section class="content-area bcorp-color-main"><div class="site-content"><div class="bcorp-row">';
  }

  function bcorp_blog_slider_shortcode($atts,$content=null,$tag ) {
    /* [bcorp_blog]
     * fullwidth (true,false)
     * filterby (category,tag,formats,portfolios)
     * ->category
     *     categories
     * ->tag
     *     tags
     * ->formats
     *     formats
     * ->portfolios
     *     portfolios
     *     portfoliolink (ajax,lightbox,portfolio)
     * size (automatic,custom)
     * ->custom
     *     customsize
     * minheight
     * count
     * offset
     * columns
     */
    $data=$GLOBALS['bcorp_shortcodes_data']->bcorp_sanitize_data($tag,$atts);
    global $post,$bcorp_caption_transitions,$bcorp_full_width_theme;
    if ($bcorp_full_width_theme && $data['fullwidth'] == 'true') $slidersize = 'fullwidth'; else $slidersize = 'standard';

    $data['buttonsize'] = 'medium';
    $data['autoplay'] = 'true';
    $data['speed']='7000';
    $textcolor = 'color: #ffffff;';
    $align = 'text-align:center;';
    $capitalize = 'text-transform:uppercase;';
    $heading = 'h5';
    $fontsize = '';
    $width = 'width:420px; max-width:85%;';

    $bcorp_slide_class = 'slide-'.$slidersize;
    $paged_offset = $data['offset'];
    $args = array(
      'offset' => $paged_offset,
      'posts_per_page' => $data['count'],
      'cat' => $data['categories'],
    );
    switch ($data['filterby']) {
      case "formats":
        $dataformats = explode(",", $data['formats']);
        if (in_array ( 'standard' , $dataformats )) {
          $formats =  array('post-format-aside', 'post-format-gallery', 'post-format-link', 'post-format-image', 'post-format-quote', 'post-format-status', 'post-format-audio', 'post-format-chat', 'post-format-video');
          foreach ($dataformats as $format) {
            if(($key = array_search($format, $formats)) !== false) {
              unset($formats[$key]);
            }

          }
          $args['tax_query'] = array( array(
                      'taxonomy' => 'post_format',
                      'field' => 'slug',
                      'terms' => $formats,
                      'operator' => 'NOT IN'
                    ) );
        } else {
          $args['tax_query'] = array( array(
                      'taxonomy' => 'post_format',
                      'field' => 'slug',
                      'terms' => $dataformats,
                    ) );
        }
        break;
      case "portfolios":
        $args['post_type']  = 'portfolio';
        if (strlen($data['portfolios'])) {
          $args['tax_query'] = array(
            array(
              'taxonomy' => 'portfolio-category',
              'field'    => 'name',
              'terms'    => explode(",", $data['portfolios'])
            ),
          );
        }
        $terms = get_terms('portfolio-category');
        break;
      case "tag":
        if (!isset($data['tags'])) $tags=''; else $args['tag__in'] = explode(",",$data['tags']);
        break;
      default:
        $terms = get_terms('category');
    }
    $posts = new WP_Query( $args );
    if ($data['size'] == 'custom') $thumb_size = $data['customsize']; else $thumb_size = '600 x 400 cropped';
    ob_start();
    if ($bcorp_full_width_theme && $data['fullwidth'] == 'true') echo $this->fullwidth_start('fullwidth');
    if (wp_is_mobile()) { $mobile = 'bcorp-mobile'; } else $mobile = '';
    if ($data['autoplay'] == 'true') $slideshow='data-autoplay="true" data-speed="'.$data['speed'].'"'; else $slideshow = 'data-autoplay="false"';
    $first = true;
    $transitions ='{$Duration:1200,x:2,y:1,$Cols:2,$Zoom:11,$Rotate:1,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Assembly:2049,$Opacity:2,$Round:{$Rotate:0.7}}|{$Duration:1200,x:-1,y:2,$Rows:2,$Zoom:11,$Rotate:1,$ChessMode:{$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Assembly:2049,$Opacity:2,$Round:{$Rotate:0.8}}';
    if (empty($bcorp_caption_transitions)) bcorp_slider_transitions();
    while ( $posts->have_posts() ) {
	    $posts->the_post();
      switch ($data['filterby']) {
        case "portfolios":
          $terms = get_the_terms( '', 'portfolio-category' );
          break;
        default:
          $terms = get_the_terms( '', 'category' );
      }
      $featured_image = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumb_size)[0];
      if ($featured_image) {
        if ($first){
          $width = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumb_size )[1];
          $height = wp_get_attachment_image_src( get_post_thumbnail_id(), $thumb_size )[2];
          $caption_width = 410;
          $caption_height = 190;
          $scale = 1;
          $left = ($width-$caption_width) /2;
          $top = ($height-$caption_height) /2;
          if ($data['style']=='carousel') {
            $scale = 0.75;
            $offset=1;
            if ($data['columns'] == 2) {
                $offset = 0.5;
                $scale = 0.975;
            }
            $caption_width = $caption_width*$scale;
            $caption_height = $caption_height*$scale;
            echo '<div class="bcorp-carousel-wrap"><div class="bcorp-blog-carousel bcorp-carousel" data-min-height="'.$data['minheight'].'" data-columns="'.$data['columns'].'" data-offset="'.$offset.'" style="position: relative; top: 0px; left: 0px;">';
            echo '<div class="bcorp-carousel-slides" data-u="slides" style="cursor: move; position: absolute; left: 0px; top: 0px; overflow: hidden;">';
          } else {
            echo '<div class="bcorp-blog-slider"><div class="bcorp-slider bcorp-standard" data-transitions="'.$transitions.'" data-min-height="'.$data['minheight'].'" style="position: relative; top: 0px; left: 0px; width: '.$width.'px; height: '.$height.'px;">';
            echo '<div data-u="slides" style="cursor: move; position: absolute; overflow: hidden; left: 0px; top: 0px; width: '.$width.'px; height: '.$height.'px;">';
          }
          $first = false;
        }
        if ($data['style']=='carousel') {
          echo '<div class="bcorp-carousel-content"><div class="bcorp-carousel-content-inner" style="font-size:0;"><img data-u="image" src="'.wp_get_attachment_image_src(get_post_thumbnail_id(), $thumb_size )[0].'" alt="'.get_post_meta(get_post_thumbnail_id(), '_wp_attachment_image_alt', true).'"/>';
          $left = (1200/$data['columns']-$caption_width) /2;
          $top = (1200/$data['columns']*$height/$width-$caption_height) /2;
        } else {
          echo '<div><img data-u="image" src="'.wp_get_attachment_image_src(get_post_thumbnail_id(), $thumb_size )[0].'" alt="'.get_post_meta(get_post_thumbnail_id(), '_wp_attachment_image_alt', true).'"/>';
        }
         echo '<div data-u="caption" data-t="FADE" data-transition-t="'.$bcorp_caption_transitions['FADE'].'" data-d="-100" data-du2="1500" style="position: absolute; left:0px; top: 0px; width: '.$width.'px; height: '.$height.'px; background-color: rgba(0, 0, 0, 0.4); padding: 0px;"></div>';
          echo '<div data-u="caption" data-t="MCLIP|T" data-transition-t="'.$bcorp_caption_transitions['MCLIP|T'].'" data-d="-600" style="position: absolute; left:'.$left.'px; top: '.$top.'px; width: '.$caption_width.'px; height: '.(50*$scale).'px; color: rgb(255, 255, 255); font-size: '.(12*$scale).'px; line-height: '.(18*$scale).'px; text-align: center;  text-transform: uppercase; ">';
          if ($data['filterby'] == 'portfolios') {
            if (in_array('portfolio-category',get_object_taxonomies('portfolio')))
                echo get_the_term_list('','portfolio-category','',' / ' ,'' );
          } elseif (in_array('category',get_object_taxonomies(get_post_type())))
              echo get_the_category_list( ' / ' );
          echo '</div>';
          echo '<div data-u="caption" data-t="MCLIP|T" data-transition-t="'.$bcorp_caption_transitions['MCLIP|T'].'" data-d="-1000"  style="position: absolute; left:'.$left.'px; top: '.($top+30*$scale).'px; width: '.$caption_width.'px; height: '.(30*$scale).'px; color: rgb(255, 255, 255); font-size: '.(27*$scale).'px; line-height: '.(27*$scale).'px; text-align: center;  text-transform: uppercase; font-weight:bold;">';
            the_title( '<a href="' . get_the_permalink() . '" rel="bookmark">', '</a>' );
          echo '</div>';
          echo '<div data-u="caption" data-t="MCLIP|T" data-transition-t="'.$bcorp_caption_transitions['MCLIP|T'].'" data-d="-1000" style="position: absolute; left:'.$left.'px; top: '.($top+70*$scale).'px; width: '.$caption_width.'px; height: '.(90*$scale).'px; color: rgb(255, 255, 255); font-size: '.(14*$scale).'px; line-height: '.(21*$scale).'px; text-align: center;  ">';
          $excerpt = substr(get_the_excerpt(),0,150);
          $length = strrpos($excerpt,' ');
          $excerpt = substr(get_the_excerpt(),0,$length);
          echo '<p>'.$excerpt.'</p></div>';
          echo '<div data-u="caption" data-t="FADE" data-transition-t="'.$bcorp_caption_transitions['FADE'].'" data-d="-700" style="position: absolute; left:'.$left.'px; top: '.($top+170*$scale).'px; width: '.$caption_width.'px; height: '.(30*$scale).'px; color: rgb(255, 255, 255); font-size: '.(16*$scale).'px; line-height: '.(16*$scale).'px; text-align: center;  ">';
          $button_style = "background-color:transparent; border: 1px solid #ffffff;";
          $buttondata =  '<a href="'.get_the_permalink().'" class="bcorp-button bcorp-button-'.$data['buttonsize'].' bcorp-table-button-label" style="'.$button_style.'">'.esc_html__('READ MORE','bcorp_framework').'</a>';
          echo $buttondata;
          echo '</div>';
          echo '</div>';
          if ($data['style']=='carousel') echo '</div>';
      }
    }
    echo '</div>';
    if ($data['style']=='slider') {
    $arrow = 2;
    $arrowpadding = 8;
    echo '<span data-u="arrowleft" class="jssora'.$arrow.'l" style="top: 123px; left: '.$arrowpadding.'px;"></span>
          <span data-u="arrowright" class="jssora'.$arrow.'r" style="top: 123px; right: '.$arrowpadding.'px;"></span>
          <div data-u="navigator" class="jssorb1" style="bottom: 16px; right: 10px;"><div data-u="prototype"></div></div>';
    }
    echo '</div></div>';
    wp_reset_postdata();
    if ($bcorp_full_width_theme && $data['fullwidth'] == 'true') echo $this->fullwidth_end();
    $return = ob_get_clean();
    if ($first == false) return $return;
  }

  function bcorp_cell_classes_reset($tag) {
    global $bcorp_cell_position, $bcorp_cell_position_small;
    $bcorp_cell_position[$tag] = 0;
    $bcorp_cell_position_small[$tag] = 0;
  }

  function bcorp_cell_classes( $tag,$width,$mobilewidth) {
    $gutter_counter = array("1-8"=>1/8,"1-7"=>1/7,"1-6"=>1/6,"1-6"=>1/6,"1-5"=>0.2,"1-4"=>0.25,"1-3"=>1/3,"2-5"=>2/5,
                            "1-2"=>1/2,"3-5"=>3/5,"2-3"=>2/3,"3-4"=>3/4,"4-5"=>4/5,"5-6"=>5/6,"1-1"=>1);
    global $bcorp_cell_position, $bcorp_cell_position_small;
    if (!isset($bcorp_cell_position[$tag])) $this->bcorp_cell_classes_reset($tag);
    if ($bcorp_cell_position[$tag]==0) $bcorp_no_gutter=' bcorp-first-cell bcorp-no-gutter'; else $bcorp_no_gutter = ' bcorp-gutter';
    if (($bcorp_cell_position_small[$tag]==0) || ($gutter_counter[$mobilewidth]==1)) $bcorp_no_gutter_small=' bcorp-no-gutter-small'; else $bcorp_no_gutter_small = '';
    $bcorp_cell_position[$tag] += $gutter_counter[$width];
    if ($bcorp_cell_position[$tag]>=1) $bcorp_cell_position[$tag]=0;
    $bcorp_cell_position_small[$tag] += $gutter_counter[$mobilewidth];
    if ($bcorp_cell_position_small[$tag]>=1) $bcorp_cell_position_small[$tag]=0;
    return 'bcorp-cell bcorp-'.$width.$bcorp_no_gutter.$bcorp_no_gutter_small.' bcorp-mobile-'.$mobilewidth.' bcorp-no-gutter-mobile';
  }

  function bcorp_link($link,$linkurl,$linksection,$linkpost,$linkpage,$linkportfolio,$linkcategory,$linktag,$linkportfoliocategory,$linkformat,$linktarget) {
    /* [bcorp_link]
     * link (none,manual,section,post,page,portfolio,category,tag,portfoliocategory,format)
     * ->manual
     *     url
     * ->section
     *     linksection
     * ->post
     *     linkpost
     * ->page
     *     linkpage
     * ->portfolio
     *     linkportfolio
     * ->category
     *     linkcategory
     * ->tag
     *     linktag
     * ->portfoliocatgory
     *     linkportfoliocategory
     * ->format
     *     linkformat
     * target (true,false)
     */
    switch ($link) {
      case "none":
        $linkurl='';
      break;
      case "manual":
      break;
      case "section":
        $linkurl="#bcorp-section-".$linksection;
        break;
      case "post":
        $linkurl=get_permalink($linkpost);
        break;
      case "page":
        $linkurl=get_permalink($linkpage);
        break;
      case "portfolio":
        $linkurl=get_permalink($linkportfolio);
        break;
      case "category":
        $linkurl=get_category_link($linkcategory);
        break;
      case "tag":
        $linkurl=get_tag_link($linktag);
        break;
      case "portfoliocategory":
        $linkurl= get_term_link($linkportfoliocategory, 'portfolio-category');
        break;
      case "format":
        $linkurl=get_post_format_link($linkformat);
     }
    if ($linkurl) {
      if ($linktarget=='true') $href['target']=' target="_blank"'; else $href['target']='';
      $href['link']=esc_url($linkurl);
      $href['start']='<a href="'.$href['link'].'"'.$href['target'].'>';
      $href['end']='</a>';
    } else {
      $href['start']='';
      $href['end']='';
      $href['link']='';
      $href['target']='';
    }
    return $href;
  }

  function bcorp_slider_shortcode($atts,$content=null,$tag ) {
    /* [bcorp_slider]
     * size (standard,fullwidth,fullscreen)
     * animation (slide,fade)
     * slidesize
     * autoplay (true,false)
     * ->true
     *     speed
     * minheight
     */
    $data=$GLOBALS['bcorp_shortcodes_data']->bcorp_sanitize_data($tag,$atts);
    global $bcorp_slider_size,$bcorp_slide_size,$bcorp_slider_transitions,$bcorp_slide_count;
    $bcorp_slider_size = $data['size'];
    $bcorp_slide_size = $data['slidesize'];
    if ($bcorp_slide_size == 'full') $bcorp_slide_size = 'large';
    $bcorp_slide_count=0;
    global $_wp_additional_image_sizes;

    if (in_array($bcorp_slide_size, array( 'thumbnail', 'medium', 'large'))) {
     $width = get_option( $bcorp_slide_size . '_size_w');
     $height = get_option( $bcorp_slide_size . '_size_h');
    } else {
     $width = $_wp_additional_image_sizes[$bcorp_slide_size]['width'];
     $height = $_wp_additional_image_sizes[$bcorp_slide_size]['height'];
    }

    $transitions = $data['transitions'];
    $transitions = '';
    if (empty($bcorp_slider_transitions)) bcorp_slider_transitions();
    $slidertransitions = explode(",", $data['transitions']);
    foreach ($slidertransitions as $slidertransition) {
      if (strlen($transitions)) $transitions .= '|';
      $transitions .= $bcorp_slider_transitions[$slidertransition];
    }
    $arrow = 2;
    if ($data['size'] == 'standard') $arrowpadding = 8; else $arrowpadding = 50;
    $output = '<div class="bcorp-slider bcorp-'.$data['size'].'" data-min-height="'.$data['minheight'].'" data-transitions="'.$transitions.'" style="position: relative; top: 0px; left: 0px; width: '.$width.'px; height: '.$height.'px;">
     <div data-u="slides" style="cursor: move; position: absolute; overflow: hidden; left: 0px; top: 0px; width: '.$width.'px; height: '.$height.'px;">
        '.do_shortcode( $content ).'
    </div>';
    if (!wp_is_mobile())  $output .='
    <span data-u="arrowleft" class="jssora'.$arrow.'l" style="top: 123px; left: '.$arrowpadding.'px;"></span>
    <span data-u="arrowright" class="jssora'.$arrow.'r" style="top: 123px; right: '.$arrowpadding.'px;"></span>
    <div data-u="navigator" class="jssorb1" style="bottom: 16px; right: 10px;"><div data-u="prototype"></div></div>';
    $output .= '</div>';
    if ($data['size'] != 'standard') return $this->fullwidth_start($data['size']).$output.$this->fullwidth_end();
    return '<div>'.$output.'</div>';
  }

    function bcorp_slide_shortcode($atts,$content=null,$tag ) {
      /* [bcorp_slide]
       * id
       * location (none,youtube,vimeo)
       * ->youtube, vimeo
       *     controls
       *     mute
       *     autoplay
       *     loop
       * ->youtube,vimeo
       *     video
       */
       $data=$GLOBALS['bcorp_shortcodes_data']->bcorp_sanitize_data($tag,$atts);
       global $bcorp_slide_size,$bcorp_slider_size,$bcorp_slide_count;
       if ($data['video'] && !wp_is_mobile()) {
         global $bcorp_background_video_id;
         $bcorp_background_video_id++;
         if ($data['controls'] == 'true') $controls = ' bcorp-video-controls'; else $controls = '';
         if ($data['mute'] == 'true') $playsettings = ' bcorp-video-mute'; else $playsettings ='';
         if ($data['autoplay'] == 'true') {
           $playsettings .= ' bcorp-video-autoplay';
           $paused = '';
           $autoplay = 1;
           if(!$bcorp_slide_count) $slideautoplay =' bcorp-'.$data['location'].'-autoplay-onload'; else $slideautoplay = '';
         } else {
           $autoplay =0;
           $paused = ' bcorp-video-playpaused';
           $slideautoplay = '';
         }
         if ($data['loop'] == 'true') $playsettings .= ' bcorp-video-loop';
         if ($data['location'] == 'vimeo') $videourl = 'http://player.vimeo.com/video/'.$data['video'].'?title=0&amp;byline=0&amp;portrait=0&amp;autoplay='.$autoplay.'&amp;loop=1&amp;autopause=0&amp;player_id=bcorp-'.$data['location'].'-'.$bcorp_background_video_id;
         else if ($data['location'] == 'youtube') $videourl = 'http://www.youtube.com/embed/'.$data['video'].'?rel=0&amp;controls=0&amp;showinfo=0&amp;enablejsapi=1&amp;origin='.get_home_url();
         $videoratio = 56.25;
         $background_video = '<div class="bcorp-video-slide-wrap'.$controls.'"><div class="bcorp-video-playpause-wrapper"><div class="bcorp-video-playpause'.$paused.'"></div></div><iframe class="bcorp-background-video bcorp-video-slide-'.$bcorp_slider_size.' bcorp-video-'.$data['location'].$slideautoplay.$playsettings.'" id="bcorp-'.$data['location'].'-'.$bcorp_background_video_id.'" src="'.$videourl.'" allowfullscreen data-video-ratio="'.$videoratio.'"></iframe></div>';
         $output = '<div>'.$background_video.do_shortcode($content).'</div>';
       } else $output = '<div><img data-u="image" src="'.wp_get_attachment_image_src( $data['id'], $bcorp_slide_size )[0].'"  alt="'.get_post_meta($data['id'], '_wp_attachment_image_alt', true).'" />'.do_shortcode($content).'</div>';
       return $output;
    }

    function bcorp_slide_cell_shortcode($atts,$content=null,$tag ) {
      /* [bcorp_slide_cell]
       * button (true,false)
       * ->true
       *     icon
       *     link^
       *     color
       *     size
       * textcolor
       * fontsize
       * lineheight
       * align (left,center,right)
       * textblock
       * left
       * top
       * width
       * height
       * t
       * t2
       * d
       * du
       * du2
       * boxcaption (true,false)
       * ->true
       *     backgroundcolor
       *     opacity
       *     radius
       *     paddingtop
       *     paddingright
       *     paddingbottom
       *     paddingleft
       */
      global $bcorp_caption_transitions;
      $data=$GLOBALS['bcorp_shortcodes_data']->bcorp_sanitize_data($tag,$atts);
      if ($data['id']) {
        $wrap1 = '<img src="'.wp_get_attachment_image_src($data['id'],'full')[0].'" alt="'.get_post_meta($data['id'], '_wp_attachment_image_alt', true).'"';
        $wrap2 = '/>';
        $data['width']=wp_get_attachment_image_src($data['id'],'full')[1];
        $data['height']=wp_get_attachment_image_src($data['id'],'full')[2];
      } else {
        $wrap1 = '<div';
        $wrap2 = '>'.strip_tags(rawurldecode($content)).'</div>';
      }

      $settings = '';

      if (strlen($data['t'])) {
        $settings .= ' data-t="'.$data['t'].'"';
        $settings .= ' data-transition-t="'.$bcorp_caption_transitions[$data['t']].'"';
      }
      if (strlen($data['t2'])) {
        $settings .= ' data-t2="'.$data['t2'].'"';
        $settings .= ' data-transition-t2="'.$bcorp_caption_transitions[$data['t2']].'"';
      }
      if (strlen($data['d'])) $settings .= ' data-d="'.$data['d'].'"';
      if (strlen($data['du'])) $settings .= ' data-du="'.$data['du'].'"';
      if (strlen($data['du2'])) $settings .= ' data-du2="'.$data['du2'].'"';

      $style = '';
      if (strlen($data['textcolor'])) $style .= ' color:'.$data['textcolor'].';';
      if (strlen($data['fontsize'])) $style .= ' font-size:'.$data['fontsize'].'px;';
      if (strlen($data['lineheight'])) $style .= ' line-height:'.$data['lineheight'].'px;';
      if (strlen($data['align'])) $style .= ' text-align:'.$data['align'].';';
      if ($data['boxcaption'] == 'true') {
        if (strlen($data['backgroundcolor'])) $style .= ' background-color:'.$data['backgroundcolor'].';';
        if (strlen($data['opacity']) && strlen($data['backgroundcolor'])) {
          $rgb = $this->bcorp_hex_to_rgb($data['backgroundcolor']);
          $style .= ' background-color:rgba('.$rgb[0].', '.$rgb[1].', '.$rgb[2].', '.$data['opacity'].');';
        }
        if (strlen($data['radius'])) $style .= ' border-radius:'.$data['radius'].'px;';
        if (strlen($data['paddingtop'])) $style .= ' padding-top:'.$data['paddingtop'].'px;';
        if (strlen($data['paddingright'])) $style .= ' padding-right:'.$data['paddingright'].'px;';
        if (strlen($data['paddingbottom'])) $style .= ' padding-bottom:'.$data['paddingbottom'].'px;';
        if (strlen($data['paddingleft'])) $style .= ' padding-left:'.$data['paddingleft'].'px;';
      }
      return $wrap1.' data-u=caption '.$settings.' style="position:absolute; left:'.$data['left'].'px; top: '.$data['top'].'px; width:'.$data['width'].'px; height:'.$data['height'].'px;'.$style.'"'.$wrap2;
  }

  function bcorp_hex_to_rgb ($hex) {
     $hex = str_replace("#", "", $hex);

     if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
     } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
     }
     return array($r, $g, $b);
  }

}

function bcorp_slider_transitions() {
  global $bcorp_slider_transitions,$bcorp_caption_transitions;
  $bcorp_slider_transitions = array(
'fade_twins'=>'{$Duration:700,$Opacity:2,$Brother:{$Duration:1000,$Opacity:2}}',
'rotate_overlap'=>'{$Duration:1200,$Zoom:11,$Rotate:-1,$Easing:{$Zoom:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:0.5},$Brother:{$Duration:1200,$Zoom:1,$Rotate:1,$Easing:$JssorEasing$.$EaseSwing,$Opacity:2,$Round:{$Rotate:0.5},$Shift:90}}',
'switch'=>'{$Duration:1400,x:0.25,$Zoom:1.5,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInSine},$Opacity:2,$ZIndex:-10,$Brother:{$Duration:1400,x:-0.25,$Zoom:1.5,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInSine},$Opacity:2,$ZIndex:-10}}',
'rotate_relay'=>'{$Duration:1200,$Zoom:11,$Rotate:1,$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:1},$ZIndex:-10,$Brother:{$Duration:1200,$Zoom:11,$Rotate:-1,$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:1},$ZIndex:-10,$Shift:600}}',
'doors'=>'{$Duration:1500,x:0.5,$Cols:2,$ChessMode:{$Column:3},$Easing:{$Left:$JssorEasing$.$EaseInOutCubic},$Opacity:2,$Brother:{$Duration:1500,$Opacity:2}}',
'rotate_in_plus_out_minus'=>'{$Duration:1500,x:-0.3,y:0.5,$Zoom:1,$Rotate:0.1,$During:{$Left:[0.6,0.4],$Top:[0.6,0.4],$Rotate:[0.6,0.4],$Zoom:[0.6,0.4]},$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Brother:{$Duration:1000,$Zoom:11,$Rotate:-0.5,$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Shift:200}}',
'fly_twins'=>'{$Duration:1500,x:0.3,$During:{$Left:[0.6,0.4]},$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true,$Brother:{$Duration:1000,x:-0.3,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}}',
'rotate_in_minus_out_plus'=>'{$Duration:1500,$Zoom:11,$Rotate:0.5,$During:{$Left:[0.4,0.6],$Top:[0.4,0.6],$Rotate:[0.4,0.6],$Zoom:[0.4,0.6]},$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Brother:{$Duration:1000,$Zoom:1,$Rotate:-0.5,$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Shift:200}}',
'rotate_axis_up_overlap'=>'{$Duration:1200,x:0.25,y:0.5,$Rotate:-0.1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Brother:{$Duration:1200,x:-0.1,y:-0.7,$Rotate:0.1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2}}',
'chess_replace_tb'=>'{$Duration:1600,x:1,$Rows:2,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Brother:{$Duration:1600,x:-1,$Rows:2,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}}',
'chess_replace_lr'=>'{$Duration:1600,y:-1,$Cols:2,$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Brother:{$Duration:1600,y:1,$Cols:2,$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}}',
'shift_tb'=>'{$Duration:1200,y:1,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Brother:{$Duration:1200,y:-1,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}}',
'shift_lr'=>'{$Duration:1200,x:1,$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Brother:{$Duration:1200,x:-1,$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}}',
'return_tb'=>'{$Duration:1200,y:-1,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$ZIndex:-10,$Brother:{$Duration:1200,y:-1,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$ZIndex:-10,$Shift:-100}}',
'return_lr'=>'{$Duration:1200,x:1,$Delay:40,$Cols:6,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$ZIndex:-10,$Brother:{$Duration:1200,x:1,$Delay:40,$Cols:6,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$ZIndex:-10,$Shift:-100}}',
'rotate_axis_down'=>'{$Duration:1500,x:-0.1,y:-0.7,$Rotate:0.1,$During:{$Left:[0.6,0.4],$Top:[0.6,0.4],$Rotate:[0.6,0.4]},$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Brother:{$Duration:1000,x:0.2,y:0.5,$Rotate:-0.1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2}}',
'extrude_replace'=>'{$Duration:1600,x:-0.2,$Delay:40,$Cols:12,$During:{$Left:[0.4,0.6]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Opacity:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$Outside:true,$Round:{$Top:0.5},$Brother:{$Duration:1000,x:0.2,$Delay:40,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:1028,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Opacity:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$Round:{$Top:0.5}}}',
'fade'=>'{$Duration:1200,$Opacity:2}',
'fade_in_l'=>'{$Duration:1200,x:0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_in_r'=>'{$Duration:1200,x:-0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_in_t'=>'{$Duration:1200,y:0.3,$During:{$Top:[0.3,0.7]},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_in_b'=>'{$Duration:1200,y:-0.3,$During:{$Top:[0.3,0.7]},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_in_lr'=>'{$Duration:1200,x:0.3,$Cols:2,$During:{$Left:[0.3,0.7]},$ChessMode:{$Column:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_in_lr_chess'=>'{$Duration:1200,y:0.3,$Cols:2,$During:{$Top:[0.3,0.7]},$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_in_tb'=>'{$Duration:1200,y:0.3,$Rows:2,$During:{$Top:[0.3,0.7]},$ChessMode:{$Row:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_in_tb_chess'=>'{$Duration:1200,x:0.3,$Rows:2,$During:{$Left:[0.3,0.7]},$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_in_corners'=>'{$Duration:1200,x:0.3,y:0.3,$Cols:2,$Rows:2,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_out_l'=>'{$Duration:1200,x:0.3,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_out_r'=>'{$Duration:1200,x:-0.3,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_out_t'=>'{$Duration:1200,y:0.3,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_out_b'=>'{$Duration:1200,y:-0.3,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_out_lr'=>'{$Duration:1200,x:0.3,$Cols:2,$SlideOut:true,$ChessMode:{$Column:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_out_lr_chess'=>'{$Duration:1200,y:-0.3,$Cols:2,$SlideOut:true,$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_out_tb'=>'{$Duration:1200,y:0.3,$Rows:2,$SlideOut:true,$ChessMode:{$Row:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_out_tb_chess'=>'{$Duration:1200,x:-0.3,$Rows:2,$SlideOut:true,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_out_corners'=>'{$Duration:1200,x:0.3,y:0.3,$Cols:2,$Rows:2,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_fly_in_l'=>'{$Duration:1200,x:0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_in_r'=>'{$Duration:1200,x:-0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_in_t'=>'{$Duration:1200,y:0.3,$During:{$Top:[0.3,0.7]},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_in_b'=>'{$Duration:1200,y:-0.3,$During:{$Top:[0.3,0.7]},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_in_lr'=>'{$Duration:1200,x:0.3,$Cols:2,$During:{$Left:[0.3,0.7]},$ChessMode:{$Column:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_in_lr_chess'=>'{$Duration:1200,y:0.3,$Cols:2,$During:{$Top:[0.3,0.7]},$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_in_tb'=>'{$Duration:1200,y:0.3,$Rows:2,$During:{$Top:[0.3,0.7]},$ChessMode:{$Row:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_in_tb_chess'=>'{$Duration:1200,x:0.3,$Rows:2,$During:{$Left:[0.3,0.7]},$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_in_corners'=>'{$Duration:1200,x:0.3,y:0.3,$Cols:2,$Rows:2,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_out_l'=>'{$Duration:1200,x:0.3,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_out_r'=>'{$Duration:1200,x:-0.3,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_out_t'=>'{$Duration:1200,y:0.3,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_out_b'=>'{$Duration:1200,y:-0.3,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_out_lr'=>'{$Duration:1200,x:0.3,$Cols:2,$SlideOut:true,$ChessMode:{$Column:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_out_lr_chess'=>'{$Duration:1200,y:0.3,$Cols:2,$SlideOut:true,$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_out_tb'=>'{$Duration:1200,y:0.3,$Rows:2,$SlideOut:true,$ChessMode:{$Row:12},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_out_tb_chess'=>'{$Duration:1200,x:0.3,$Rows:2,$SlideOut:true,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_fly_out_corners'=>'{$Duration:1200,x:0.3,y:0.3,$Cols:2,$Rows:2,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true}',
'fade_clip_in_h'=>'{$Duration:1200,$Delay:20,$Clip:3,$Assembly:260,$Easing:{$Clip:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_clip_in_v'=>'{$Duration:1200,$Delay:20,$Clip:12,$Assembly:260,$Easing:{$Clip:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_clip_out_h'=>'{$Duration:1200,$Delay:20,$Clip:3,$SlideOut:true,$Assembly:260,$Easing:{$Clip:$JssorEasing$.$EaseOutCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_clip_out_v'=>'{$Duration:1200,$Delay:20,$Clip:12,$SlideOut:true,$Assembly:260,$Easing:{$Clip:$JssorEasing$.$EaseOutCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'fade_stairs'=>'{$Duration:800,$Delay:30,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Opacity:2}',
'fade_random'=>'{$Duration:1000,$Delay:80,$Cols:8,$Rows:4,$Opacity:2}',
'fade_swirl'=>'{$Duration:800,$Delay:30,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Opacity:2}',
'fade_zigzag'=>'{$Duration:800,$Delay:30,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Opacity:2}',
'swing_outside_in_stairs'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_in_zigzag'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_in_swirl'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_in_random'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_in_random_chess'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:3,$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_in_square'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSquare,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_out_stairs'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_out_zigzag'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_out_swirl'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_out_random'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_out_random_chess'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$ChessMode:{$Column:3,$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_outside_out_square'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}}',
'swing_inside_in_stairs'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}}',
'swing_inside_in_zigzag'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}}',
'swing_inside_in_swirl'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}}',
'swing_inside_in_random'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}}',
'swing_inside_in_random_chess'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:3,$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}}',
'swing_inside_in_square'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSquare,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}}',
'swing_inside_out_zigzag'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}}',
'swing_inside_out_swirl'=>'{$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}}',
'dodge_dance_outside_in_stairs'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_in_swirl'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_in_zigzag'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_in_random'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_in_random_chess'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_in_square'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSquare,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseLinear},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_out_stairs'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.1,0.9],$Top:[0.1,0.9]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_out_swirl'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.1,0.9],$Top:[0.1,0.9]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_out_zigzag'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.1,0.9],$Top:[0.1,0.9]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_out_random'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_out_random_chess'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_outside_out_square'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseLinear},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_in_stairs'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_in_swirl'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_in_zigzag'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_in_random'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_in_random_chess'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_in_square'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSquare,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseLinear},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_out_stairs'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.1,0.9],$Top:[0.1,0.9]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_out_swirl'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.1,0.9],$Top:[0.1,0.9]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_out_zigzag'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.1,0.9],$Top:[0.1,0.9]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_out_random'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_out_random_chess'=>'{$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_dance_inside_out_square'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseLinear},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_in_stairs'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_in_swirl'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_in_zigzag'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_in_random'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_in_random_chess'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_in_square'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_out_stairs'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_out_swirl'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_out_zigzag'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_out_random'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_out_random_chess'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_outside_out_square'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_in_stairs'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_in_swirl'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_in_zigzag'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_in_random'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_in_random_chess'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_in_square'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_out_stairs'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_out_swirl'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_out_zigzag'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_out_random'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_out_random_chess'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_pet_inside_out_square'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}}',
'dodge_outside_out_stairs'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_out_swirl'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_out_zigzag'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_out_random'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_out_random_chess'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Assembly:260,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_out_square'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_in_stairs'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_in_swirl'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_in_zigzag'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_in_random'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_in_random_chess'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Assembly:260,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_outside_in_square'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_out_stairs'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_out_swirl'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_out_zigzag'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_out_random'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_out_random_chess'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Assembly:260,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_out_square'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_in_stairs'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_in_swirl'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_in_zigzag'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_in_random'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_in_random_chess'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Assembly:260,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_in_square'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_in_tl'=>'{$Duration:1200,x:0.3,y:0.3,$Delay:60,$Zoom:1,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_in_tr'=>'{$Duration:1200,x:-0.3,y:0.3,$Delay:60,$Zoom:1,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_in_bl'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:60,$Zoom:1,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_in_br'=>'{$Duration:1200,x:-0.3,y:-0.3,$Delay:60,$Zoom:1,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_out_tl'=>'{$Duration:1200,x:0.3,y:0.3,$Delay:60,$Zoom:1,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_out_tr'=>'{$Duration:1200,x:-0.3,y:0.3,$Delay:60,$Zoom:1,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_out_bl'=>'{$Duration:1200,x:0.3,y:-0.3,$Delay:60,$Zoom:1,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}',
'dodge_inside_out_br'=>'{$Duration:1200,x:-0.3,y:-0.3,$Delay:60,$Zoom:1,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:0.8,$Top:0.8}}',
'flutter_outside_in'=>'{$Duration:1800,x:1,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:0.8}}',
'flutter_outside_in_wind'=>'{$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:1.3}}',
'flutter_outside_in_swirl'=>'{$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:1.3}}',
'flutter_outside_in_column'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:150,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true,$Round:{$Top:2}}',
'flutter_outside_out'=>'{$Duration:1800,x:1,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:0.8}}',
'flutter_outside_out_wind'=>'{$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:1.3}}',
'flutter_outside_out_swirl'=>'{$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:1.3}}',
'flutter_outside_out_column'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:150,$Cols:12,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true,$Round:{$Top:2}}',
'flutter_inside_in'=>'{$Duration:1800,x:1,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:0.8}}',
'flutter_inside_in_wind'=>'{$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:1.3}}',
'flutter_inside_in_swirl'=>'{$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:1.3}}',
'flutter_inside_in_column'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:150,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Top:2}}',
'flutter_inside_out'=>'{$Duration:1800,x:1,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:0.8}}',
'flutter_inside_out_wind'=>'{$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:1.3}}',
'flutter_inside_out_swirl'=>'{$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:1.3}}',
'flutter_inside_out_column'=>'{$Duration:1500,x:0.2,y:-0.1,$Delay:150,$Cols:12,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Top:2}}',
'rotate_vdouble_plus_in'=>'{$Duration:1200,x:-1,y:2,$Rows:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_hdouble_plus_in'=>'{$Duration:1200,x:2,y:1,$Cols:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_vdouble_minus_in'=>'{$Duration:1200,x:-0.5,y:1,$Rows:2,$Zoom:1,$Rotate:1,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_hdouble_minus_in'=>'{$Duration:1200,x:0.5,y:0.3,$Cols:2,$Zoom:1,$Rotate:1,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_vdouble_plus_out'=>'{$Duration:1000,x:-1,y:2,$Rows:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.85}}',
'rotate_hdouble_plus_out'=>'{$Duration:1000,x:4,y:2,$Cols:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_vdouble_minus_out'=>'{$Duration:1000,x:-0.5,y:1,$Rows:2,$Zoom:1,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_hdouble_minus_out'=>'{$Duration:1000,x:0.5,y:0.3,$Cols:2,$Zoom:1,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_vfork_plus_in'=>'{$Duration:1200,x:-4,y:2,$Rows:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Row:28},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_hfork_plus_in'=>'{$Duration:1200,x:1,y:2,$Cols:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Column:19},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_vfork_plus_out'=>'{$Duration:1000,x:-3,y:1,$Rows:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:28},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_hfork_plus_out'=>'{$Duration:1000,x:1,y:2,$Cols:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:19},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_zoom_plus_in'=>'{$Duration:1200,$Zoom:11,$Rotate:1,$Easing:{$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_zoom_plus_in_l'=>'{$Duration:1200,x:4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_zoom_plus_in_r'=>'{$Duration:1200,x:-4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_zoom_plus_in_t'=>'{$Duration:1200,y:4,$Zoom:11,$Rotate:1,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_zoom_plus_in_b'=>'{$Duration:1200,y:-4,$Zoom:11,$Rotate:1,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_zoom_plus_in_tl'=>'{$Duration:1200,x:4,y:4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_zoom_plus_in_tr'=>'{$Duration:1200,x:-4,y:4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_zoom_plus_in_bl'=>'{$Duration:1200,x:4,y:-4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_zoom_plus_in_br'=>'{$Duration:1200,x:-4,y:-4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}}',
'rotate_zoom_plus_out'=>'{$Duration:1000,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_zoom_plus_out_l'=>'{$Duration:1000,x:4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_zoom_plus_out_r'=>'{$Duration:1000,x:-4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_zoom_plus_out_t'=>'{$Duration:1000,y:4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_zoom_plus_out_b'=>'{$Duration:1000,y:-4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_zoom_plus_out_tl'=>'{$Duration:1000,x:4,y:4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_zoom_plus_out_tr'=>'{$Duration:1000,x:-4,y:4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_zoom_plus_out_bl'=>'{$Duration:1000,x:4,y:-4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_zoom_plus_out_br'=>'{$Duration:1000,x:-4,y:-4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'rotate_zoom_minus_in'=>'{$Duration:1200,$Zoom:1,$Rotate:1,$During:{$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:{$Zoom:$JssorEasing$.$EaseSwing,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseSwing},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_in_l'=>'{$Duration:1200,x:0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:{$Left:$JssorEasing$.$EaseSwing,$Zoom:$JssorEasing$.$EaseSwing,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseSwing},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_in_r'=>'{$Duration:1200,x:-0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:{$Left:$JssorEasing$.$EaseSwing,$Zoom:$JssorEasing$.$EaseSwing,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseSwing},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_in_t'=>'{$Duration:1200,y:0.6,$Zoom:1,$Rotate:1,$During:{$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:{$Zoom:$JssorEasing$.$EaseSwing,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseSwing},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_in_b'=>'{$Duration:1200,y:-0.6,$Zoom:1,$Rotate:1,$During:{$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:{$Zoom:$JssorEasing$.$EaseSwing,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseSwing},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_in_tl'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:{$Zoom:$JssorEasing$.$EaseSwing,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseSwing},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_in_tr'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:{$Zoom:$JssorEasing$.$EaseSwing,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseSwing},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_in_bl'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:{$Zoom:$JssorEasing$.$EaseSwing,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseSwing},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_in_br'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:1,$Rotate:1,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Zoom:[0.2,0.8],$Rotate:[0.2,0.8]},$Easing:{$Zoom:$JssorEasing$.$EaseSwing,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseSwing},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_out'=>'{$Duration:1000,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_out_l'=>'{$Duration:1000,x:0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_out_r'=>'{$Duration:1000,x:-0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_out_t'=>'{$Duration:1000,y:0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_out_b'=>'{$Duration:1000,y:-0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_out_tl'=>'{$Duration:1000,x:0.5,y:0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_out_tr'=>'{$Duration:1000,x:-0.5,y:0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_out_bl'=>'{$Duration:1000,x:0.5,y:-0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'rotate_zoom_minus_out_br'=>'{$Duration:1000,x:-0.5,y:-0.5,$Zoom:1,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'zoom_vdouble_plus_in'=>'{$Duration:1200,y:2,$Rows:2,$Zoom:11,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_hdouble_plus_in'=>'{$Duration:1200,x:4,$Cols:2,$Zoom:11,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_vdouble_minus_in'=>'{$Duration:1200,y:1,$Rows:2,$Zoom:1,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_hdouble_minus_in'=>'{$Duration:1200,x:0.5,$Cols:2,$Zoom:1,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_vdouble_plus_out'=>'{$Duration:1200,y:2,$Rows:2,$Zoom:11,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_hdouble_plus_out'=>'{$Duration:1200,x:4,$Cols:2,$Zoom:11,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_vdouble_minus_out'=>'{$Duration:1200,y:1,$Rows:2,$Zoom:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_hdouble_minus_out'=>'{$Duration:1200,x:0.5,$Cols:2,$Zoom:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_plus_in'=>'{$Duration:1000,$Zoom:11,$Easing:{$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_plus_in_l'=>'{$Duration:1000,x:4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_plus_in_r'=>'{$Duration:1000,x:-4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2,$Round:{$Top:2.5}}',
'zoom_plus_in_t'=>'{$Duration:1000,y:4,$Zoom:11,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_plus_in_b'=>'{$Duration:1000,y:-4,$Zoom:11,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_plus_in_tl'=>'{$Duration:1000,x:4,y:4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_plus_in_tr'=>'{$Duration:1000,x:-4,y:4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_plus_in_bl'=>'{$Duration:1000,x:4,y:-4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_plus_in_br'=>'{$Duration:1000,x:-4,y:-4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_plus_out'=>'{$Duration:1000,$Zoom:11,$SlideOut:true,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_plus_out_l'=>'{$Duration:1000,x:4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_plus_out_r'=>'{$Duration:1000,x:-4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_plus_out_t'=>'{$Duration:1000,y:4,$Zoom:11,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_plus_out_b'=>'{$Duration:1000,y:-4,$Zoom:11,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_plus_out_tl'=>'{$Duration:1000,x:4,y:4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_plus_out_tr'=>'{$Duration:1000,x:-4,y:4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_plus_out_bl'=>'{$Duration:1000,x:4,y:-4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_plus_out_br'=>'{$Duration:1000,x:-4,y:-4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_minus_in'=>'{$Duration:1200,$Zoom:1,$Easing:{$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_minus_in_l'=>'{$Duration:1200,x:0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_minus_in_r'=>'{$Duration:1200,x:-0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_minus_in_t'=>'{$Duration:1200,y:0.6,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_minus_in_b'=>'{$Duration:1200,y:-0.6,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_minus_in_tl'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_minus_in_tr'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_minus_in_bl'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_minus_in_br'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'zoom_minus_out'=>'{$Duration:1000,$Zoom:1,$SlideOut:true,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_minus_out_l'=>'{$Duration:1000,x:1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_minus_out_r'=>'{$Duration:1000,x:-1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_minus_out_t'=>'{$Duration:1000,y:1,$Zoom:1,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_minus_out_b'=>'{$Duration:1000,y:-1,$Zoom:1,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_minus_out_tl'=>'{$Duration:1000,x:1,y:1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_minus_out_tr'=>'{$Duration:1000,x:-1,y:1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_minus_out_bl'=>'{$Duration:1000,x:1,y:-1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'zoom_minus_out_br'=>'{$Duration:1000,x:-1,y:-1,$Zoom:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'collapse_stairs'=>'{$Duration:1000,$Delay:30,$Cols:8,$Rows:4,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2049,$Easing:$JssorEasing$.$EaseOutQuad}',
'collapse_swirl'=>'{$Duration:500,$Delay:30,$Cols:8,$Rows:4,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Easing:$JssorEasing$.$EaseOutQuad}',
'collapse_square'=>'{$Duration:800,$Delay:300,$Cols:8,$Rows:4,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Easing:$JssorEasing$.$EaseOutQuad}',
'collapse_rectangle_cross'=>'{$Duration:800,$Delay:300,$Cols:8,$Rows:4,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Easing:$JssorEasing$.$EaseOutQuad}',
'collapse_rectangle'=>'{$Duration:800,$Delay:300,$Cols:8,$Rows:4,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Easing:$JssorEasing$.$EaseOutQuad}',
'collapse_cross'=>'{$Duration:800,$Delay:300,$Cols:8,$Rows:4,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCross,$Easing:$JssorEasing$.$EaseOutQuad}',
'collapse_circle'=>'{$Duration:800,$Delay:200,$Cols:8,$Rows:4,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:2049}',
'collapse_zigzag'=>'{$Duration:500,$Delay:30,$Cols:8,$Rows:4,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Easing:$JssorEasing$.$EaseOutQuad}',
'collapse_random'=>'{$Duration:1000,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$SlideOut:true,$Easing:$JssorEasing$.$EaseOutQuad}',
'clip_and_chess_in'=>'{$Duration:1200,y:-1,$Cols:8,$Rows:4,$Clip:15,$During:{$Top:[0.5,0.5],$Clip:[0,0.5]},$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:12},$ScaleClip:0.5}',
'clip_and_chess_out'=>'{$Duration:1200,y:-1,$Cols:8,$Rows:4,$Clip:15,$During:{$Top:[0.5,0.5],$Clip:[0,0.5]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:12},$ScaleClip:0.5}',
'clip_and_oblique_chess_in'=>'{$Duration:1200,x:-1,y:-1,$Cols:6,$Rows:6,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Clip:[0,0.2]},$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Clip:$JssorEasing$.$EaseSwing},$ScaleClip:0.5}',
'clip_and_oblique_chess_out'=>'{$Duration:1200,x:-1,y:-1,$Cols:6,$Rows:6,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8],$Clip:[0,0.2]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Clip:$JssorEasing$.$EaseSwing},$ScaleClip:0.5}',
'clip_and_wave_in'=>'{$Duration:4000,x:-1,y:0.45,$Delay:80,$Cols:12,$Clip:15,$During:{$Left:[0.35,0.65],$Top:[0.35,0.65],$Clip:[0,0.15]},$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:2049,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseOutQuad},$ScaleClip:0.7,$Round:{$Top:4}}',
'clip_and_wave_out'=>'{$Duration:4000,x:-1,y:0.45,$Delay:80,$Cols:12,$Clip:15,$During:{$Left:[0.35,0.65],$Top:[0.35,0.65],$Clip:[0,0.15]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:2049,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseOutQuad},$ScaleClip:0.7,$Round:{$Top:4}}',
'clip_and_jump_in'=>'{$Duration:4000,x:-1,y:0.7,$Delay:80,$Cols:12,$Clip:11,$Move:true,$During:{$Left:[0.35,0.65],$Top:[0.35,0.65],$Clip:[0,0.1]},$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:2049,$Easing:{$Left:$JssorEasing$.$EaseOutQuad,$Top:$JssorEasing$.$EaseOutJump,$Clip:$JssorEasing$.$EaseOutQuad},$ScaleClip:0.7,$Round:{$Top:4}}',
'clip_and_jump_out'=>'{$Duration:4000,x:-1,y:0.7,$Delay:80,$Cols:12,$Clip:11,$Move:true,$During:{$Left:[0.35,0.65],$Top:[0.35,0.65],$Clip:[0,0.1]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:2049,$Easing:{$Left:$JssorEasing$.$EaseOutQuad,$Top:$JssorEasing$.$EaseOutJump,$Clip:$JssorEasing$.$EaseOutQuad},$ScaleClip:0.7,$Round:{$Top:4}}',
'expand_stairs'=>'{$Duration:1000,$Delay:30,$Cols:8,$Rows:4,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:$JssorEasing$.$EaseInQuad}',
'expand_straight'=>'{$Duration:1000,$Cols:3,$Rows:2,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:$JssorEasing$.$EaseInBounce}',
'expand_swirl'=>'{$Duration:500,$Delay:30,$Cols:8,$Rows:4,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Easing:$JssorEasing$.$EaseInQuad}',
'expand_square'=>'{$Duration:800,$Delay:300,$Cols:8,$Rows:4,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Easing:$JssorEasing$.$EaseInQuad}',
'expand_rectangle_cross'=>'{$Duration:800,$Delay:300,$Cols:8,$Rows:4,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Easing:$JssorEasing$.$EaseInQuad}',
'expand_rectangle'=>'{$Duration:800,$Delay:300,$Cols:8,$Rows:4,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Easing:$JssorEasing$.$EaseInQuad}',
'expand_cross'=>'{$Duration:800,$Delay:300,$Cols:8,$Rows:4,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationCross,$Easing:$JssorEasing$.$EaseInQuad}',
'expand_zigzag'=>'{$Duration:500,$Delay:30,$Cols:8,$Rows:4,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:$JssorEasing$.$EaseInQuad}',
'expand_random'=>'{$Duration:1000,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$Easing:$JssorEasing$.$EaseInQuad}',
'dominoes_stripe'=>'{$Duration:2000,y:-1,$Delay:60,$Cols:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:$JssorEasing$.$EaseOutJump,$Round:{$Top:1.5}}',
'extrude_out_stripe'=>'{$Duration:1000,x:-0.2,$Delay:40,$Cols:12,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Opacity:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$Outside:true,$Round:{$Top:0.5}}',
'extrude_in_stripe'=>'{$Duration:1000,x:0.2,$Delay:40,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Opacity:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$Outside:true,$Round:{$Top:0.5}}',
'horizontal_blind_stripe'=>'{$Duration:400,$Delay:100,$Rows:7,$Clip:4,$Formation:$JssorSlideshowFormations$.$FormationStraight}',
'vertical_blind_stripe'=>'{$Duration:400,$Delay:100,$Cols:10,$Clip:2,$Formation:$JssorSlideshowFormations$.$FormationStraight}',
'horizontal_stripe'=>'{$Duration:1000,$Rows:6,$Clip:4}',
'vertical_stripe'=>'{$Duration:1000,$Cols:8,$Clip:1}',
'horizontal_moving_stripe'=>'{$Duration:1000,$Rows:6,$Clip:4,$Move:true}',
'vertical_moving_stripe'=>'{$Duration:1000,$Cols:8,$Clip:1,$Move:true}',
'horizontal_fade_stripe'=>'{$Duration:600,$Delay:100,$Rows:7,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Opacity:2}',
'vertical_fade_stripe'=>'{$Duration:600,$Delay:100,$Cols:10,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Opacity:2}',
'horizontal_fly_stripe'=>'{$Duration:800,x:1,$Delay:80,$Rows:8,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'vertical_fly_stripe'=>'{$Duration:800,y:1,$Delay:80,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:513,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'horizontal_chess_stripe'=>'{$Duration:1000,x:-1,$Rows:6,$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Row:3}}',
'vertical_chess_stripe'=>'{$Duration:1000,y:-1,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:12}}',
'horizontal_random_fade_stripe'=>'{$Duration:600,$Delay:80,$Rows:6,$Opacity:2}',
'vertical_random_fade_stripe'=>'{$Duration:600,$Delay:80,$Cols:10,$Opacity:2}',
'horizontal_bounce_stripe'=>'{$Duration:800,$Delay:150,$Rows:5,$Clip:8,$Move:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:264,$Easing:$JssorEasing$.$EaseInBounce}',
'vertical_bounce_stripe'=>'{$Duration:800,$Delay:150,$Cols:10,$Clip:1,$Move:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:264,$Easing:$JssorEasing$.$EaseInBounce}',
'wave_out'=>'{$Duration:1500,y:-0.5,$Delay:60,$Cols:12,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:$JssorEasing$.$EaseInWave,$Round:{$Top:1.5}}',
'wave_out_eagle'=>'{$Duration:1500,y:-0.5,$Delay:60,$Cols:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Easing:$JssorEasing$.$EaseInWave,$Round:{$Top:1.5}}',
'wave_out_swirl'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Round:{$Top:1.5}}',
'wave_out_zigzag'=>'{$Duration:1500,x:1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Round:{$Top:1.5}}',
'wave_out_square'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Round:{$Top:1.5}}',
'wave_out_rectangle'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Round:{$Top:1.5}}',
'wave_out_circle'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Round:{$Top:1.5}}',
'wave_out_cross'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCross,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Round:{$Top:1.5}}',
'wave_out_rectangle_cross'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Round:{$Top:1.5}}',
'wave_in'=>'{$Duration:1500,y:-0.5,$Delay:60,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:$JssorEasing$.$EaseInWave,$Round:{$Top:1.5}}',
'wave_in_eagle'=>'{$Duration:1500,y:-0.5,$Delay:60,$Cols:15,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Easing:$JssorEasing$.$EaseInWave,$Round:{$Top:1.5}}',
'wave_in_swirl'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInWave},$Round:{$Top:1.5}}',
'wave_in_zigzag'=>'{$Duration:1500,x:1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInWave},$Round:{$Top:1.5}}',
'wave_in_square'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInWave},$Round:{$Top:1.5}}',
'wave_in_rectangle'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInWave},$Round:{$Top:1.5}}',
'wave_in_circle'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInWave},$Round:{$Top:1.5}}',
'wave_in_cross'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationCross,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInWave},$Round:{$Top:1.5}}',
'wave_in_rectangle_cross'=>'{$Duration:1500,x:-1,y:0.5,$Delay:60,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInWave},$Round:{$Top:1.5}}',
'jump_out_straight'=>'{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}}',
'jump_out_swirl'=>'{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}}',
'jump_out_zigzag'=>'{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}}',
'jump_out_square'=>'{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}}',
'jump_out_square_with_chess'=>'{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}}',
'jump_out_rectangle'=>'{$Duration:1500,x:-1,y:0.5,$Delay:800,$Cols:8,$Rows:4,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}}',
'jump_out_circle'=>'{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}}',
'jump_out_rectangle_cross'=>'{$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}}',
'jump_in_straight'=>'{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}}',
'jump_in_swirl'=>'{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}}',
'jump_in_zigzag'=>'{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}}',
'jump_in_square'=>'{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}}',
'jump_in_square_with_chess'=>'{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}}',
'jump_in_rectangle'=>'{$Duration:1500,x:-1,y:-0.5,$Delay:800,$Cols:8,$Rows:4,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}}',
'jump_in_circle'=>'{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}}',
'jump_in_rectangle_cross'=>'{$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}}',
'parabola_swirl_in'=>'{$Duration:600,x:-1,y:1,$Delay:100,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:264,$Easing:{$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'parabola_swirl_out'=>'{$Duration:600,x:-1,y:1,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:264,$Easing:{$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'parabola_zigzag_in'=>'{$Duration:600,x:1,y:1,$Delay:60,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'parabola_zigzag_out'=>'{$Duration:600,x:1,y:1,$Delay:60,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'parabola_stairs_in'=>'{$Duration:600,x:-1,y:1,$Delay:30,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInQuart,$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'parabola_stairs_out'=>'{$Duration:600,x:-1,y:1,$Delay:30,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInQuart,$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'float_right_random'=>'{$Duration:600,x:-1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'float_up_random'=>'{$Duration:600,y:1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'float_up_random_with_chess'=>'{$Duration:600,x:1,y:-1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'float_right_zigzag'=>'{$Duration:600,x:-1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'float_up_zigzag'=>'{$Duration:600,y:1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:264,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'float_up_zigzag_with_chess'=>'{$Duration:600,x:-1,y:-1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:1028,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'float_right_swirl'=>'{$Duration:600,x:-1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'float_up_swirl'=>'{$Duration:600,y:1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2049,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'float_up_swirl_with_chess'=>'{$Duration:600,x:1,y:1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'fly_right_random'=>'{$Duration:600,x:1,$Delay:50,$Cols:8,$Rows:4,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'fly_up_random'=>'{$Duration:600,y:-1,$Delay:50,$Cols:8,$Rows:4,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'fly_up_random_with_chess'=>'{$Duration:600,x:-1,y:1,$Delay:50,$Cols:8,$Rows:4,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'fly_right_zigzag'=>'{$Duration:600,x:1,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'fly_up_zigzag'=>'{$Duration:600,y:-1,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:264,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'fly_up_zigzag_with_chess'=>'{$Duration:600,x:1,y:1,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:1028,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'fly_right_swirl'=>'{$Duration:600,x:1,$Delay:50,$Cols:8,$Rows:4,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'fly_up_swirl'=>'{$Duration:600,y:-1,$Delay:50,$Cols:8,$Rows:4,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2049,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'fly_up_swirl_with_chess'=>'{$Duration:600,x:-1,y:-1,$Delay:50,$Cols:8,$Rows:4,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2}',
'slide_down'=>'{$Duration:500,y:1,$Easing:$JssorEasing$.$EaseInQuad}',
'slide_right'=>'{$Duration:400,x:1,$Easing:$JssorEasing$.$EaseInQuad}',
'bounce_down'=>'{$Duration:1000,y:1,$Easing:$JssorEasing$.$EaseInBounce}',
'bounce_right'=>'{$Duration:1000,x:1,$Easing:$JssorEasing$.$EaseInBounce}'
);

$bcorp_caption_transitions = array(
'L'=>'{$Duration:900,x:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'R'=>'{$Duration:900,x:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'T'=>'{$Duration:900,y:0.6,$Easing:{$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'B'=>'{$Duration:900,y:-0.6,$Easing:{$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'TL'=>'{$Duration:900,x:0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'TR'=>'{$Duration:900,x:-0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'BL'=>'{$Duration:900,x:0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'BR'=>'{$Duration:900,x:-0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'L|IB'=>'{$Duration:1200,x:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutBack},$Opacity:2}',
'R|IB'=>'{$Duration:1200,x:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutBack},$Opacity:2}',
'T|IB'=>'{$Duration:1200,y:0.6,$Easing:{$Top:$JssorEasing$.$EaseInOutBack},$Opacity:2}',
'B|IB'=>'{$Duration:1200,y:-0.6,$Easing:{$Top:$JssorEasing$.$EaseInOutBack},$Opacity:2}',
'TL|IB'=>'{$Duration:1200,x:0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutBack,$Top:$JssorEasing$.$EaseInOutBack},$Opacity:2}',
'TR|IB'=>'{$Duration:1200,x:-0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutBack,$Top:$JssorEasing$.$EaseInOutBack},$Opacity:2}',
'BL|IB'=>'{$Duration:1200,x:0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutBack,$Top:$JssorEasing$.$EaseInOutBack},$Opacity:2}',
'BR|IB'=>'{$Duration:1200,x:-0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutBack,$Top:$JssorEasing$.$EaseInOutBack},$Opacity:2}',
'L|IE'=>'{$Duration:1200,x:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic},$Opacity:2}',
'R|IE'=>'{$Duration:1200,x:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic},$Opacity:2}',
'T|IE'=>'{$Duration:1200,y:0.6,$Easing:{$Top:$JssorEasing$.$EaseInOutElastic},$Opacity:2}',
'B|IE'=>'{$Duration:1200,y:-0.6,$Easing:{$Top:$JssorEasing$.$EaseInOutElastic},$Opacity:2}',
'TL|IE'=>'{$Duration:1200,x:0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic,$Top:$JssorEasing$.$EaseInOutElastic},$Opacity:2}',
'TR|IE'=>'{$Duration:1200,x:-0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic,$Top:$JssorEasing$.$EaseInOutElastic},$Opacity:2}',
'BL|IE'=>'{$Duration:1200,x:0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic,$Top:$JssorEasing$.$EaseInOutElastic},$Opacity:2}',
'BR|IE'=>'{$Duration:1200,x:-0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic,$Top:$JssorEasing$.$EaseInOutElastic},$Opacity:2}',
'L|EP'=>'{$Duration:1200,x:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo},$Opacity:2}',
'R|EP'=>'{$Duration:1200,x:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo},$Opacity:2}',
'T|EP'=>'{$Duration:1200,y:0.6,$Easing:{$Top:$JssorEasing$.$EaseInOutExpo},$Opacity:2}',
'B|EP'=>'{$Duration:1200,y:-0.6,$Easing:{$Top:$JssorEasing$.$EaseInOutExpo},$Opacity:2}',
'TL|EP'=>'{$Duration:1200,x:0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Top:$JssorEasing$.$EaseInOutExpo},$Opacity:2}',
'TR|EP'=>'{$Duration:1200,x:-0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Top:$JssorEasing$.$EaseInOutExpo},$Opacity:2}',
'BL|EP'=>'{$Duration:1200,x:0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Top:$JssorEasing$.$EaseInOutExpo},$Opacity:2}',
'BR|EP'=>'{$Duration:1200,x:-0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Top:$JssorEasing$.$EaseInOutExpo},$Opacity:2}',
'L*'=>'{$Duration:900,x:0.6,$Rotate:-0.05,$Easing:{$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'R*'=>'{$Duration:900,x:-0.6,$Rotate:0.05,$Easing:{$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'T*'=>'{$Duration:900,y:0.6,$Rotate:-0.05,$Easing:{$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'B*'=>'{$Duration:900,y:-0.6,$Rotate:0.05,$Easing:{$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'TL*'=>'{$Duration:900,x:0.6,y:0.6,$Rotate:-0.05,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'TR*'=>'{$Duration:900,x:-0.6,y:0.6,$Rotate:0.05,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'BL*'=>'{$Duration:900,x:0.6,y:-0.6,$Rotate:-0.05,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'BR*'=>'{$Duration:900,x:-0.6,y:-0.6,$Rotate:0.05,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInOutSine},$Opacity:2}',
'L*IE'=>'{$Duration:1200,x:0.6,$Zoom:3,$Rotate:-0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'R*IE'=>'{$Duration:1200,x:-0.6,$Zoom:3,$Rotate:-0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'T*IE'=>'{$Duration:1200,y:0.6,$Zoom:3,$Rotate:-0.3,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'B*IE'=>'{$Duration:1200,y:-0.6,$Zoom:3,$Rotate:-0.3,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'TL*IE'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:3,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'TR*IE'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:3,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'BL*IE'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:3,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'BR*IE'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:3,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'L*IB'=>'{$Duration:1200,x:0.6,$Zoom:3,$Rotate:-0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2}',
'R*IB'=>'{$Duration:1200,x:-0.6,$Zoom:3,$Rotate:-0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2}',
'T*IB'=>'{$Duration:1200,y:0.6,$Zoom:3,$Rotate:-0.3,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2}',
'B*IB'=>'{$Duration:1200,y:-0.6,$Zoom:3,$Rotate:-0.3,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2}',
'TL*IB'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:3,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2}',
'TR*IB'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:3,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2}',
'BL*IB'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:3,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2}',
'BR*IB'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:3,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2}',
'L-*IB'=>'{$Duration:900,x:0.7,$Rotate:-0.5,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2,$During:{$Left:[0.2,0.8]}}',
'R-*IB'=>'{$Duration:900,x:-0.7,$Rotate:0.5,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2,$During:{$Left:[0.2,0.8]}}',
'T-*IB'=>'{$Duration:900,y:0.7,$Rotate:-0.5,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2,$During:{$Top:[0.2,0.8]}}',
'B-*IB'=>'{$Duration:900,y:-0.7,$Rotate:0.5,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2,$During:{$Top:[0.2,0.8]}}',
'TL-*IB'=>'{$Duration:900,x:0.7,y:0.7,$Rotate:-0.5,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2,$During:{$Left:[0.2,0.8]}}',
'TR-*IB'=>'{$Duration:900,x:-0.7,y:0.7,$Rotate:0.5,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2,$During:{$Left:[0.2,0.8]}}',
'BL-*IB'=>'{$Duration:900,x:0.7,y:-0.7,$Rotate:-0.5,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2,$During:{$Left:[0.2,0.8]}}',
'BR-*IB'=>'{$Duration:900,x:-0.7,y:-0.7,$Rotate:0.5,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInBack},$Opacity:2,$During:{$Left:[0.2,0.8]}}',
'L*IW'=>'{$Duration:1200,x:0.6,$Zoom:3,$Rotate:2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInWave},$Opacity:2}',
'R*IW'=>'{$Duration:1200,x:-0.6,$Zoom:3,$Rotate:2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInWave},$Opacity:2}',
'T*IW'=>'{$Duration:1200,y:0.6,$Zoom:3,$Rotate:2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInWave},$Opacity:2}',
'B*IW'=>'{$Duration:1200,y:-0.6,$Zoom:3,$Rotate:2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInWave},$Opacity:2}',
'TL*IW'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:3,$Rotate:2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInWave},$Opacity:2}',
'TR*IW'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:3,$Rotate:2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInWave},$Opacity:2}',
'BL*IW'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:3,$Rotate:2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInWave},$Opacity:2}',
'BR*IW'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:3,$Rotate:2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Rotate:$JssorEasing$.$EaseInWave},$Opacity:2}',
'L|IE*IE'=>'{$Duration:1800,x:0.5,$Zoom:11,$Rotate:-1.5,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic,$Zoom:$JssorEasing$.$EaseInElastic,$Rotate:$JssorEasing$.$EaseInOutElastic},$Opacity:2,$During:{$Zoom:[0,0.8],$Opacity:[0,0.7]},$Round:{$Rotate:0.5}}',
'R|IE*IE'=>'{$Duration:1800,x:-0.5,$Zoom:11,$Rotate:-1.5,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic,$Zoom:$JssorEasing$.$EaseInElastic,$Rotate:$JssorEasing$.$EaseInOutElastic},$Opacity:2,$During:{$Zoom:[0,0.8],$Opacity:[0,0.7]},$Round:{$Rotate:0.5}}',
'T|IE*IE'=>'{$Duration:1800,y:0.8,$Zoom:11,$Rotate:-1.5,$Easing:{$Top:$JssorEasing$.$EaseInOutElastic,$Zoom:$JssorEasing$.$EaseInElastic,$Rotate:$JssorEasing$.$EaseInOutElastic},$Opacity:2,$During:{$Zoom:[0,0.8],$Opacity:[0,0.7]},$Round:{$Rotate:0.5}}',
'B|IE*IE'=>'{$Duration:1800,y:-0.8,$Zoom:11,$Rotate:-1.5,$Easing:{$Top:$JssorEasing$.$EaseInOutElastic,$Zoom:$JssorEasing$.$EaseInElastic,$Rotate:$JssorEasing$.$EaseInOutElastic},$Opacity:2,$During:{$Zoom:[0,0.8],$Opacity:[0,0.7]},$Round:{$Rotate:0.5}}',
'TL|IE*IE'=>'{$Duration:1800,x:0.4,y:0.8,$Zoom:11,$Rotate:-1.5,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic,$Top:$JssorEasing$.$EaseInOutElastic,$Zoom:$JssorEasing$.$EaseInElastic,$Rotate:$JssorEasing$.$EaseInOutElastic},$Opacity:2,$During:{$Zoom:[0,0.8],$Opacity:[0,0.7]},$Round:{$Rotate:0.5}}',
'TR|IE*IE'=>'{$Duration:1800,x:-0.4,y:0.8,$Zoom:11,$Rotate:-1.5,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic,$Top:$JssorEasing$.$EaseInOutElastic,$Zoom:$JssorEasing$.$EaseInElastic,$Rotate:$JssorEasing$.$EaseInOutElastic},$Opacity:2,$During:{$Zoom:[0,0.8],$Opacity:[0,0.7]},$Round:{$Rotate:0.5}}',
'BL|IE*IE'=>'{$Duration:1800,x:0.4,y:-0.8,$Zoom:11,$Rotate:-1.5,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic,$Top:$JssorEasing$.$EaseInOutElastic,$Zoom:$JssorEasing$.$EaseInElastic,$Rotate:$JssorEasing$.$EaseInOutElastic},$Opacity:2,$During:{$Zoom:[0,0.8],$Opacity:[0,0.7]},$Round:{$Rotate:0.5}}',
'BR|IE*IE'=>'{$Duration:1800,x:-0.4,y:-0.8,$Zoom:11,$Rotate:-1.5,$Easing:{$Left:$JssorEasing$.$EaseInOutElastic,$Top:$JssorEasing$.$EaseInOutElastic,$Zoom:$JssorEasing$.$EaseInElastic,$Rotate:$JssorEasing$.$EaseInOutElastic},$Opacity:2,$During:{$Zoom:[0,0.8],$Opacity:[0,0.7]},$Round:{$Rotate:0.5}}',
'CLIP'=>'{$Duration:900,$Clip:15,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic},$Opacity:2}',
'CLIP|LR'=>'{$Duration:900,$Clip:3,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic},$Opacity:2}',
'CLIP|TB'=>'{$Duration:900,$Clip:12,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic},$Opacity:2}',
'CLIP|L'=>'{$Duration:900,$Clip:1,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic},$Opacity:2}',
'CLIP|R'=>'{$Duration:900,$Clip:2,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic},$Opacity:2}',
'CLIP|T'=>'{$Duration:900,$Clip:4,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic},$Opacity:2}',
'CLIP|B'=>'{$Duration:900,$Clip:8,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic},$Opacity:2}',
'MCLIP|L'=>'{$Duration:900,$Clip:1,$Move:true,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic}}',
'MCLIP|R'=>'{$Duration:900,$Clip:2,$Move:true,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic}}',
'MCLIP|T'=>'{$Duration:900,$Clip:4,$Move:true,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic}}',
'MCLIP|B'=>'{$Duration:900,$Clip:8,$Move:true,$Easing:{$Clip:$JssorEasing$.$EaseInOutCubic}}',
'ZM'=>'{$Duration:900,$Zoom:1,$Easing:$JssorEasing$.$EaseInCubic,$Opacity:2}',
'ZM|P30'=>'{$Duration:900,$Zoom:1.3,$Easing:$JssorEasing$.$EaseInCubic,$Opacity:2}',
'ZM|P50'=>'{$Duration:900,$Zoom:1.5,$Easing:$JssorEasing$.$EaseInCubic,$Opacity:2}',
'ZM|P70'=>'{$Duration:900,$Zoom:1.7,$Easing:$JssorEasing$.$EaseInCubic,$Opacity:2}',
'ZM|P80'=>'{$Duration:900,$Zoom:1.8,$Easing:$JssorEasing$.$EaseInCubic,$Opacity:2}',
'ZMF|2'=>'{$Duration:900,$Zoom:3,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'ZMF|3'=>'{$Duration:900,$Zoom:4,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'ZMF|4'=>'{$Duration:900,$Zoom:5,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'ZMF|5'=>'{$Duration:900,$Zoom:6,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'ZMF|10'=>'{$Duration:900,$Zoom:11,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}',
'ZML|L'=>'{$Duration:900,x:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZML|R'=>'{$Duration:900,x:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZML|T'=>'{$Duration:900,y:0.6,$Zoom:11,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZML|B'=>'{$Duration:900,y:-0.6,$Zoom:11,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZML|TL'=>'{$Duration:900,x:0.6,y:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZML|TR'=>'{$Duration:900,x:-0.6,y:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZML|BL'=>'{$Duration:900,x:0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZML|BR'=>'{$Duration:900,x:-0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZML|IE|L'=>'{$Duration:1200,x:0.6,$Zoom:6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'ZML|IE|R'=>'{$Duration:1200,x:-0.6,$Zoom:6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'ZML|IE|T'=>'{$Duration:1200,y:0.6,$Zoom:6,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'ZML|IE|B'=>'{$Duration:1200,y:-0.6,$Zoom:6,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'ZML|IE|TL'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'ZML|IE|TR'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'ZML|IE|BL'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'ZML|IE|BR'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInElastic},$Opacity:2}',
'ZMS|L'=>'{$Duration:900,x:0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZMS|R'=>'{$Duration:900,x:-0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZMS|T'=>'{$Duration:900,y:0.6,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZMS|B'=>'{$Duration:900,y:-0.6,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZMS|TL'=>'{$Duration:900,x:0.6,y:0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZMS|TR'=>'{$Duration:900,x:-0.6,y:0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZMS|BL'=>'{$Duration:900,x:0.6,y:-0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZMS|BR'=>'{$Duration:900,x:-0.6,y:-0.6,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZM*JDN|LT'=>'{$Duration:1200,x:0.8,y:0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'ZM*JDN|LB'=>'{$Duration:1200,x:0.8,y:-0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'ZM*JDN|RT'=>'{$Duration:1200,x:-0.8,y:0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'ZM*JDN|RB'=>'{$Duration:1200,x:-0.8,y:-0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'ZM*JDN|TL'=>'{$Duration:1200,x:0.5,y:0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'ZM*JDN|TR'=>'{$Duration:1200,x:-0.5,y:0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'ZM*JDN|BL'=>'{$Duration:1200,x:0.5,y:-0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'ZM*JDN|BR'=>'{$Duration:1200,x:-0.5,y:-0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'ZM*JUP|LT'=>'{$Duration:1200,x:0.8,y:0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'ZM*JUP|LB'=>'{$Duration:1200,x:0.8,y:-0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'ZM*JUP|RT'=>'{$Duration:1200,x:-0.8,y:0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'ZM*JUP|RB'=>'{$Duration:1200,x:-0.8,y:-0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'ZM*JUP|TL'=>'{$Duration:1200,x:0.5,y:0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'ZM*JUP|TR'=>'{$Duration:1200,x:-0.5,y:0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'ZM*JUP|BL'=>'{$Duration:1200,x:0.5,y:-0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'ZM*JUP|BR'=>'{$Duration:1200,x:-0.5,y:-0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'ZM*JDN|LB*'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.75}}',
'ZM*JDN|RB*'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.75}}',
'ZM*JDN1|L'=>'{$Duration:1200,x:0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Zoom:0.5}}',
'ZM*JDN1|R'=>'{$Duration:1200,x:-0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Zoom:0.5}}',
'ZM*JDN1|T'=>'{$Duration:1200,y:0.5,$Zoom:11,$Easing:{$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Zoom:0.5}}',
'ZM*JDN1|B'=>'{$Duration:1200,y:-0.5,$Zoom:11,$Easing:{$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Zoom:0.5}}',
'ZM*JUP1|L'=>'{$Duration:1200,x:0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Zoom:0.5}}',
'ZM*JUP1|R'=>'{$Duration:1200,x:-0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Zoom:0.5}}',
'ZM*JUP1|T'=>'{$Duration:1200,y:0.5,$Zoom:11,$Easing:{$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Zoom:0.5}}',
'ZM*JUP1|B'=>'{$Duration:1200,y:-0.5,$Zoom:11,$Easing:{$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Zoom:0.5}}',
'ZM*WVC|LT'=>'{$Duration:1200,x:0.5,y:0.3,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVC|LB'=>'{$Duration:1200,x:0.5,y:-0.3,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVC|RT'=>'{$Duration:1200,x:-0.5,y:0.3,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVC|RB'=>'{$Duration:1200,x:-0.5,y:-0.3,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVC|TL'=>'{$Duration:1200,x:0.3,y:0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVC|TR'=>'{$Duration:1200,x:-0.3,y:0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVC|BL'=>'{$Duration:1200,x:0.3,y:-0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVC|BR'=>'{$Duration:1200,x:-0.3,y:-0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVR|LT'=>'{$Duration:1200,x:0.5,y:0.3,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVR|LB'=>'{$Duration:1200,x:0.5,y:-0.3,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVR|RT'=>'{$Duration:1200,x:-0.5,y:0.3,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVR|RB'=>'{$Duration:1200,x:-0.5,y:-0.3,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVR|TL'=>'{$Duration:1200,x:0.3,y:0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVR|TR'=>'{$Duration:1200,x:-0.3,y:0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVR|BL'=>'{$Duration:1200,x:0.3,y:-0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WVR|BR'=>'{$Duration:1200,x:-0.3,y:-0.5,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Rotate:0.8}}',
'ZM*WV*J1|LT'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.5}}',
'ZM*WV*J1|LB'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.5}}',
'ZM*WV*J1|RT'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.5}}',
'ZM*WV*J1|RB'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.5}}',
'ZM*WV*J1|TL'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]},$Round:{$Left:0.5,$Top:0.3}}',
'ZM*WV*J1|TR'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]},$Round:{$Left:0.5,$Top:0.3}}',
'ZM*WV*J1|BL'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]},$Round:{$Left:0.5,$Top:0.3}}',
'ZM*WV*J1|BR'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]},$Round:{$Left:0.5,$Top:0.3}}',
'ZM*WV*J2|LT'=>'{$Duration:1200,x:0.8,y:0.4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZM*WV*J2|LB'=>'{$Duration:1200,x:0.8,y:-0.4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZM*WV*J2|RT'=>'{$Duration:1200,x:-0.8,y:0.4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZM*WV*J2|RB'=>'{$Duration:1200,x:-0.8,y:-0.4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZM*WV*J2|TL'=>'{$Duration:1200,x:0.4,y:0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZM*WV*J2|TR'=>'{$Duration:1200,x:-0.4,y:0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZM*WV*J2|BL'=>'{$Duration:1200,x:0.4,y:-0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZM*WV*J2|BR'=>'{$Duration:1200,x:-0.4,y:-0.8,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'ZM*WV*J3|LT'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInSine,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Top:0.5}}',
'ZM*WV*J3|LB'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInSine,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Top:0.5}}',
'ZM*WV*J3|RT'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInSine,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Top:0.5}}',
'ZM*WV*J3|RB'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInSine,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Top:0.5}}',
'ZM*WV*J3|TL'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseInSine,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Left:0.5}}',
'ZM*WV*J3|TR'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseInSine,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Left:0.5}}',
'ZM*WV*J3|BL'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseInSine,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Left:0.5}}',
'ZM*WV*J3|BR'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseInSine,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Left:0.5}}',
'RTT'=>'{$Duration:900,$Rotate:1,$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT|90'=>'{$Duration:900,$Rotate:1,$Opacity:2,$Round:{$Rotate:0.25}}',
'RTT|360'=>'{$Duration:900,$Rotate:1,$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2}',
'RTT|0'=>'{$Duration:900,$Zoom:1,$Rotate:1,$Easing:{$Zoom:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT|2'=>'{$Duration:900,$Zoom:3,$Rotate:1,$Easing:{$Zoom:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT|3'=>'{$Duration:900,$Zoom:4,$Rotate:1,$Easing:{$Zoom:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT|4'=>'{$Duration:900,$Zoom:5,$Rotate:1,$Easing:{$Zoom:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT|5'=>'{$Duration:900,$Zoom:6,$Rotate:1,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'RTT|10'=>'{$Duration:900,$Zoom:11,$Rotate:1,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}}',
'RTTL|L'=>'{$Duration:900,x:0.6,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}}',
'RTTL|R'=>'{$Duration:900,x:-0.6,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}}',
'RTTL|T'=>'{$Duration:900,y:0.6,$Zoom:11,$Rotate:1,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}}',
'RTTL|B'=>'{$Duration:900,y:-0.6,$Zoom:11,$Rotate:1,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}}',
'RTTL|TL'=>'{$Duration:900,x:0.6,y:0.6,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}}',
'RTTL|TR'=>'{$Duration:900,x:-0.6,y:0.6,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}}',
'RTTL|BL'=>'{$Duration:900,x:0.6,y:-0.6,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}}',
'RTTL|BR'=>'{$Duration:900,x:-0.6,y:-0.6,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}}',
'RTTS|L'=>'{$Duration:900,x:0.6,$Zoom:1,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Zoom:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2,$Round:{$Rotate:1.2}}',
'RTTS|R'=>'{$Duration:900,x:-0.6,$Zoom:1,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Zoom:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2,$Round:{$Rotate:1.2}}',
'RTTS|T'=>'{$Duration:900,y:0.6,$Zoom:1,$Rotate:1,$Easing:{$Top:$JssorEasing$.$EaseInQuad,$Zoom:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2,$Round:{$Rotate:1.2}}',
'RTTS|B'=>'{$Duration:900,y:-0.6,$Zoom:1,$Rotate:1,$Easing:{$Top:$JssorEasing$.$EaseInQuad,$Zoom:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2,$Round:{$Rotate:1.2}}',
'RTTS|TL'=>'{$Duration:900,x:0.6,y:0.6,$Zoom:1,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Zoom:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2,$Round:{$Rotate:1.2}}',
'RTTS|TR'=>'{$Duration:900,x:-0.6,y:0.6,$Zoom:1,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Zoom:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2,$Round:{$Rotate:1.2}}',
'RTTS|BL'=>'{$Duration:900,x:0.6,y:-0.6,$Zoom:1,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Zoom:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2,$Round:{$Rotate:1.2}}',
'RTTS|BR'=>'{$Duration:900,x:-0.6,y:-0.6,$Zoom:1,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Zoom:$JssorEasing$.$EaseInQuad,$Rotate:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2,$Round:{$Rotate:1.2}}',
'RTT*JDN|L'=>'{$Duration:1200,x:0.3,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JDN|R'=>'{$Duration:1200,x:-0.3,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JDN|T'=>'{$Duration:1200,y:0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Top:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JDN|B'=>'{$Duration:1200,y:-0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Top:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JUP|L'=>'{$Duration:1200,x:0.3,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JUP|R'=>'{$Duration:1200,x:-0.3,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JUP|T'=>'{$Duration:1200,y:0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JUP|B'=>'{$Duration:1200,y:-0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JDN|LT'=>'{$Duration:1200,x:0.8,y:0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JDN|LB'=>'{$Duration:1200,x:0.8,y:-0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JDN|RT'=>'{$Duration:1200,x:-0.8,y:0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JDN|RB'=>'{$Duration:1200,x:-0.8,y:-0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JDN|TL'=>'{$Duration:1200,x:0.5,y:0.8,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JDN|TR'=>'{$Duration:1200,x:-0.5,y:0.8,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JDN|BL'=>'{$Duration:1200,x:0.5,y:-0.8,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JDN|BR'=>'{$Duration:1200,x:-0.5,y:-0.8,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JUP|LT'=>'{$Duration:1200,x:0.8,y:0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JUP|LB'=>'{$Duration:1200,x:0.8,y:-0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JUP|RT'=>'{$Duration:1200,x:-0.8,y:0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JUP|RB'=>'{$Duration:1200,x:-0.8,y:-0.5,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]}}',
'RTT*JUP|TL'=>'{$Duration:1200,x:0.5,y:0.8,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JUP|TR'=>'{$Duration:1200,x:-0.5,y:0.8,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JUP|BL'=>'{$Duration:1200,x:0.5,y:-0.8,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JUP|BR'=>'{$Duration:1200,x:-0.5,y:-0.8,$Zoom:11,$Rotate:0.2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]}}',
'RTT*JDN|LB*'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:11,$Rotate:-1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.75,$Rotate:0.5}}',
'RTT*JDN|RB*'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:11,$Rotate:-1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.75,$Rotate:0.5}}',
'RTT*JDN1|L'=>'{$Duration:1200,x:0.5,$Zoom:6,$Rotate:0.25,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JDN1|R'=>'{$Duration:1200,x:-0.5,$Zoom:6,$Rotate:0.25,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JDN1|T'=>'{$Duration:1200,y:0.5,$Zoom:6,$Rotate:0.25,$Easing:{$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JDN1|B'=>'{$Duration:1200,y:-0.5,$Zoom:6,$Rotate:0.25,$Easing:{$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JUP1|L'=>'{$Duration:1200,x:0.5,$Zoom:6,$Rotate:0.25,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JUP1|R'=>'{$Duration:1200,x:-0.5,$Zoom:6,$Rotate:0.25,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JUP1|T'=>'{$Duration:1200,y:0.5,$Zoom:6,$Rotate:0.25,$Easing:{$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JUP1|B'=>'{$Duration:1200,y:-0.5,$Zoom:6,$Rotate:0.25,$Easing:{$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JDN1|TL'=>'{$Duration:1200,x:0.5,y:1,$Zoom:6,$Rotate:0.25,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JDN1|TR'=>'{$Duration:1200,x:-0.5,y:1,$Zoom:6,$Rotate:0.25,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JDN1|BL'=>'{$Duration:1200,x:-0.5,y:-1,$Zoom:6,$Rotate:0.25,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JUP1|TL'=>'{$Duration:1200,x:0.5,y:1,$Zoom:6,$Rotate:0.25,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JUP1|TR'=>'{$Duration:1200,x:-0.5,y:1,$Zoom:6,$Rotate:0.25,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*JUP1|BL'=>'{$Duration:1200,x:-0.5,y:-1,$Zoom:6,$Rotate:0.25,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*WVC|LT'=>'{$Duration:1500,x:2,y:0.3,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2}',
'RTT*WVC|LB'=>'{$Duration:1500,x:2,y:-0.3,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2}',
'RTT*WVC|RT'=>'{$Duration:1500,x:-2,y:0.3,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2}',
'RTT*WVC|RB'=>'{$Duration:1500,x:-2,y:-0.3,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2}',
'RTT*WVC|TL'=>'{$Duration:1500,x:0.3,y:2,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'RTT*WVC|TR'=>'{$Duration:1500,x:-0.3,y:2,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'RTT*WVC|BL'=>'{$Duration:1500,x:0.3,y:-2,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'RTT*WVC|BR'=>'{$Duration:1500,x:-0.3,y:-2,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'RTT*WVR|LT'=>'{$Duration:1500,x:2,y:0.3,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave},$Opacity:2}',
'RTT*WVR|LB'=>'{$Duration:1500,x:2,y:-0.3,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave},$Opacity:2}',
'RTT*WVR|RT'=>'{$Duration:1500,x:-2,y:0.3,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave},$Opacity:2}',
'RTT*WVR|RB'=>'{$Duration:1500,x:-2,y:-0.3,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave},$Opacity:2}',
'RTT*WVR|TL'=>'{$Duration:1500,x:0.3,y:2,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'RTT*WVR|TR'=>'{$Duration:1500,x:-0.3,y:2,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'RTT*WVR|BL'=>'{$Duration:1500,x:0.3,y:-2,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'RTT*WVR|BR'=>'{$Duration:1500,x:-0.3,y:-2,$Zoom:11,$Rotate:0.3,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'RTT*WV*J1|LT'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:11,$Rotate:-0.8,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.5,$Rotate:0.4}}',
'RTT*WV*J1|LB'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:11,$Rotate:-0.8,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.5,$Rotate:0.4}}',
'RTT*WV*J1|RT'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:11,$Rotate:-0.8,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.5,$Rotate:0.4}}',
'RTT*WV*J1|RB'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:11,$Rotate:-0.8,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Top:[0,0.5]},$Round:{$Left:0.3,$Top:0.5,$Rotate:0.4}}',
'RTT*WV*J1|TL'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:11,$Rotate:-0.8,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]},$Round:{$Left:0.5,$Top:0.3,$Rotate:0.4}}',
'RTT*WV*J1|TR'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:11,$Rotate:-0.8,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]},$Round:{$Left:0.5,$Top:0.3,$Rotate:0.4}}',
'RTT*WV*J1|BL'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:11,$Rotate:-0.8,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]},$Round:{$Left:0.5,$Top:0.3,$Rotate:0.4}}',
'RTT*WV*J1|BR'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:11,$Rotate:-0.8,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$During:{$Left:[0,0.5]},$Round:{$Left:0.5,$Top:0.3,$Rotate:0.4}}',
'RTT*WV*J2|LT'=>'{$Duration:1200,x:0.8,y:0.4,$Zoom:11,$Rotate:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*WV*J2|LB'=>'{$Duration:1200,x:0.8,y:-0.4,$Zoom:11,$Rotate:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*WV*J2|RT'=>'{$Duration:1200,x:-0.8,y:0.4,$Zoom:11,$Rotate:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*WV*J2|RB'=>'{$Duration:1200,x:-0.8,y:-0.4,$Zoom:11,$Rotate:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*WV*J2|TL'=>'{$Duration:1200,x:0.4,y:0.8,$Zoom:11,$Rotate:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*WV*J2|TR'=>'{$Duration:1200,x:-0.4,y:0.8,$Zoom:11,$Rotate:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*WV*J2|BL'=>'{$Duration:1200,x:0.4,y:-0.8,$Zoom:11,$Rotate:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*WV*J2|BR'=>'{$Duration:1200,x:-0.4,y:-0.8,$Zoom:11,$Rotate:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.5}}',
'RTT*WV*J3|LT'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:11,$Rotate:-1,$Easing:{$Left:$JssorEasing$.$EaseInSine,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Top:0.5,$Rotate:0.5}}',
'RTT*WV*J3|LB'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:11,$Rotate:-1,$Easing:{$Left:$JssorEasing$.$EaseInSine,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Top:0.5,$Rotate:0.5}}',
'RTT*WV*J3|RT'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:11,$Rotate:-1,$Easing:{$Left:$JssorEasing$.$EaseInSine,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Top:0.5,$Rotate:0.5}}',
'RTT*WV*J3|RB'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:11,$Rotate:-1,$Easing:{$Left:$JssorEasing$.$EaseInSine,$Top:$JssorEasing$.$EaseOutJump,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Top:0.5,$Rotate:0.5}}',
'RTT*WV*J3|TL'=>'{$Duration:1200,x:0.6,y:0.6,$Zoom:11,$Rotate:-1,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseInSine,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Left:0.5,$Rotate:0.5}}',
'RTT*WV*J3|TR'=>'{$Duration:1200,x:-0.6,y:0.6,$Zoom:11,$Rotate:-1,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseInSine,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Left:0.5,$Rotate:0.5}}',
'RTT*WV*J3|BL'=>'{$Duration:1200,x:0.6,y:-0.6,$Zoom:11,$Rotate:-1,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseInSine,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Left:0.5,$Rotate:0.5}}',
'RTT*WV*J3|BR'=>'{$Duration:1200,x:-0.6,y:-0.6,$Zoom:11,$Rotate:-1,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseInSine,$Zoom:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Left:0.5,$Rotate:0.5}}',
'DDG|TL'=>'{$Duration:1200,x:0.3,y:0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Opacity:2,$During:{$Left:[0,0.8],$Top:[0,0.8]},$Round:{$Left:0.8,$Top:0.8}}',
'DDG|TR'=>'{$Duration:1200,x:-0.3,y:0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Opacity:2,$During:{$Left:[0,0.8],$Top:[0,0.8]},$Round:{$Left:0.8,$Top:0.8}}',
'DDG|BL'=>'{$Duration:1200,x:0.3,y:-0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Opacity:2,$During:{$Left:[0,0.8],$Top:[0,0.8]},$Round:{$Left:0.8,$Top:0.8}}',
'DDG|BR'=>'{$Duration:1200,x:-0.3,y:-0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Opacity:2,$During:{$Left:[0,0.8],$Top:[0,0.8]},$Round:{$Left:0.8,$Top:0.8}}',
'DDGDANCE|LT'=>'{$Duration:1800,x:0.3,y:0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseOutQuad},$Opacity:2,$During:{$Left:[0,0.8],$Top:[0,0.8]},$Round:{$Left:0.8,$Top:2.5}}',
'DDGDANCE|RT'=>'{$Duration:1800,x:-0.3,y:0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseOutQuad},$Opacity:2,$During:{$Left:[0,0.8],$Top:[0,0.8]},$Round:{$Left:0.8,$Top:2.5}}',
'DDGDANCE|LB'=>'{$Duration:1800,x:0.3,y:-0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseOutQuad},$Opacity:2,$During:{$Left:[0,0.8],$Top:[0,0.8]},$Round:{$Left:0.8,$Top:2.5}}',
'DDGDANCE|RB'=>'{$Duration:1800,x:-0.3,y:-0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Zoom:$JssorEasing$.$EaseOutQuad},$Opacity:2,$During:{$Left:[0,0.8],$Top:[0,0.8]},$Round:{$Left:0.8,$Top:2.5}}',
'DDGPET|LT'=>'{$Duration:1800,x:0.2,y:0.05,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0,0.7]},$Round:{$Left:0.8,$Top:2.5}}',
'DDGPET|LB'=>'{$Duration:1800,x:0.2,y:-0.05,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0,0.7]},$Round:{$Left:0.8,$Top:2.5}}',
'DDGPET|RT'=>'{$Duration:1800,x:-0.2,y:0.05,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0,0.7]},$Round:{$Left:0.8,$Top:2.5}}',
'DDGPET|RB'=>'{$Duration:1800,x:-0.2,y:-0.05,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0,0.7]},$Round:{$Left:0.8,$Top:2.5}}',
'FLTTR|L'=>'{$Duration:900,x:0.2,y:-0.1,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$Opacity:2,$Round:{$Top:1.3}}',
'FLTTR|R'=>'{$Duration:900,x:-0.2,y:-0.1,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$Opacity:2,$Round:{$Top:1.3}}',
'FLTTR|T'=>'{$Duration:900,x:0.1,y:0.2,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:1.3}}',
'FLTTR|B'=>'{$Duration:900,x:0.1,y:-0.2,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:1.3}}',
'FLTTRWN|LT'=>'{$Duration:1800,x:0.5,y:0.2,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.1,0.7]},$Round:{$Top:1.3}}',
'FLTTRWN|LB'=>'{$Duration:1800,x:0.5,y:-0.2,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.1,0.7]},$Round:{$Top:1.3}}',
'FLTTRWN|RT'=>'{$Duration:1800,x:-0.5,y:0.2,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.1,0.7]},$Round:{$Top:1.3}}',
'FLTTRWN|RB'=>'{$Duration:1800,x:-0.5,y:-0.2,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.1,0.7]},$Round:{$Top:1.3}}',
'FLTTRWN|TL'=>'{$Duration:1800,x:0.2,y:0.5,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseInOutSine,$Left:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Top:[0,0.7],$Left:[0.1,0.7]},$Round:{$Left:1.3}}',
'FLTTRWN|TR'=>'{$Duration:1800,x:-0.2,y:0.5,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseInOutSine,$Left:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Top:[0,0.7],$Left:[0.1,0.7]},$Round:{$Left:1.3}}',
'FLTTRWN|BL'=>'{$Duration:1800,x:0.2,y:-0.5,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseInOutSine,$Left:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Top:[0,0.7],$Left:[0.1,0.7]},$Round:{$Left:1.3}}',
'FLTTRWN|BR'=>'{$Duration:1800,x:-0.2,y:-0.5,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseInOutSine,$Left:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Top:[0,0.7],$Left:[0.1,0.7]},$Round:{$Left:1.3}}',
'LATENCY|LT'=>'{$Duration:1200,x:0.5,y:0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.1,0.7]},$Round:{$Top:0.4}}',
'LATENCY|LB'=>'{$Duration:1200,x:0.5,y:-0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.1,0.7]},$Round:{$Top:0.4}}',
'LATENCY|RT'=>'{$Duration:1200,x:-0.5,y:0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.1,0.7]},$Round:{$Top:0.4}}',
'LATENCY|RB'=>'{$Duration:1200,x:-0.5,y:-0.3,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.1,0.7]},$Round:{$Top:0.4}}',
'LATENCY|TL'=>'{$Duration:1200,x:0.5,y:0.5,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseOutSine,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0.1,0.7],$Top:[0,0.7]},$Round:{$Left:0.4}}',
'LATENCY|TR'=>'{$Duration:1200,x:-0.5,y:0.5,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseOutSine,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0.1,0.7],$Top:[0,0.7]},$Round:{$Left:0.4}}',
'LATENCY|BL'=>'{$Duration:1200,x:0.5,y:-0.5,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseOutSine,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0.1,0.7],$Top:[0,0.7]},$Round:{$Left:0.4}}',
'LATENCY|BR'=>'{$Duration:1200,x:-0.5,y:-0.5,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseOutSine,$Zoom:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$During:{$Left:[0.1,0.7],$Top:[0,0.7]},$Round:{$Left:0.4}}',
'TORTUOUS|HL'=>'{$Duration:1800,x:0.2,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Left:[0,0.7]},$Round:{$Left:1.3}}',
'TORTUOUS|HR'=>'{$Duration:1800,x:-0.2,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Left:[0,0.7]},$Round:{$Left:1.3}}',
'TORTUOUS|VB'=>'{$Duration:1800,y:-0.2,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Top:[0,0.7]},$Round:{$Top:1.3}}',
'TORTUOUS|VT'=>'{$Duration:1800,y:0.2,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Top:[0,0.7]},$Round:{$Top:1.3}}',
'TORTUOUS|LT'=>'{$Duration:1800,x:1,y:0.2,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Top:[0,0.7]},$Round:{$Left:1.3}}',
'TORTUOUS|LB'=>'{$Duration:1800,x:1,y:-0.2,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Top:[0,0.7]},$Round:{$Left:1.3}}',
'TORTUOUS|RT'=>'{$Duration:1800,x:-1,y:0.2,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Top:[0,0.7]},$Round:{$Left:1.3}}',
'TORTUOUS|RB'=>'{$Duration:1800,x:-1,y:-0.2,$Zoom:1,$Easing:{$Top:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Top:[0,0.7]},$Round:{$Left:1.3}}',
'TORTUOUS|TL'=>'{$Duration:1800,x:0.2,y:1,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Left:[0,0.7]},$Round:{$Top:1.3}}',
'TORTUOUS|TR'=>'{$Duration:1800,x:-0.2,y:1,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Left:[0,0.7]},$Round:{$Top:1.3}}',
'TORTUOUS|BL'=>'{$Duration:1800,x:0.2,y:-1,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Left:[0,0.7]},$Round:{$Top:1.3}}',
'TORTUOUS|BR'=>'{$Duration:1800,x:-0.2,y:-1,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Zoom:$JssorEasing$.$EaseOutCubic},$Opacity:2,$During:{$Left:[0,0.7]},$Round:{$Top:1.3}}',
'SPACESHIP|LT'=>'{$Duration:1200,x:1,y:0.1,$Zoom:3,$Rotate:-0.1,$Easing:{$Left:$JssorEasing$.$EaseInQuint,$Top:$JssorEasing$.$EaseInWave,$Opacity:$JssorEasing$.$EaseInQuint},$Opacity:2}',
'SPACESHIP|LB'=>'{$Duration:1200,x:1,y:-0.1,$Zoom:3,$Rotate:-0.1,$Easing:{$Left:$JssorEasing$.$EaseInQuint,$Top:$JssorEasing$.$EaseInWave,$Opacity:$JssorEasing$.$EaseInQuint},$Opacity:2}',
'SPACESHIP|RT'=>'{$Duration:1200,x:-1,y:0.1,$Zoom:3,$Rotate:0.1,$Easing:{$Left:$JssorEasing$.$EaseInQuint,$Top:$JssorEasing$.$EaseInWave,$Opacity:$JssorEasing$.$EaseInQuint},$Opacity:2}',
'SPACESHIP|RB'=>'{$Duration:1200,x:-1,y:-0.1,$Zoom:3,$Rotate:0.1,$Easing:{$Left:$JssorEasing$.$EaseInQuint,$Top:$JssorEasing$.$EaseInWave,$Opacity:$JssorEasing$.$EaseInQuint},$Opacity:2}',
'ATTACK|LT'=>'{$Duration:1500,x:0.5,y:0.1,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.3,0.7]},$Round:{$Top:1.3}}',
'ATTACK|LB'=>'{$Duration:1500,x:0.5,y:-0.1,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.3,0.7]},$Round:{$Top:1.3}}',
'ATTACK|RT'=>'{$Duration:1500,x:-0.5,y:0.1,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.3,0.7]},$Round:{$Top:1.3}}',
'ATTACK|RB'=>'{$Duration:1500,x:-0.5,y:-0.1,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2,$During:{$Left:[0,0.7],$Top:[0.3,0.7]},$Round:{$Top:1.3}}',
'ATTACK|TL'=>'{$Duration:1500,x:0.1,y:0.5,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseInExpo},$Opacity:2,$During:{$Left:[0.3,0.7],$Top:[0,0.7]},$Round:{$Left:1.3}}',
'ATTACK|TR'=>'{$Duration:1500,x:-0.1,y:0.5,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseInExpo},$Opacity:2,$During:{$Left:[0.3,0.7],$Top:[0,0.7]},$Round:{$Left:1.3}}',
'ATTACK|BL'=>'{$Duration:1500,x:0.1,y:-0.5,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseInExpo},$Opacity:2,$During:{$Left:[0.3,0.7],$Top:[0,0.7]},$Round:{$Left:1.3}}',
'ATTACK|BR'=>'{$Duration:1500,x:-0.1,y:-0.5,$Zoom:1,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseInExpo},$Opacity:2,$During:{$Left:[0.3,0.7],$Top:[0,0.7]},$Round:{$Left:1.3}}',
'LISTV|L'=>'{$Duration:1500,x:0.8,$Clip:4,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Left:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTV|R'=>'{$Duration:1500,x:-0.8,$Clip:4,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Left:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTH|L'=>'{$Duration:1500,x:0.8,$Clip:1,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Left:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTH|R'=>'{$Duration:1500,x:-0.8,$Clip:1,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Left:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTVC|L'=>'{$Duration:1500,x:0.8,$Clip:12,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Left:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTVC|R'=>'{$Duration:1500,x:-0.8,$Clip:12,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Left:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTVC|B'=>'{$Duration:1500,y:-0.8,$Clip:12,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Top:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTVC|T'=>'{$Duration:1500,y:0.8,$Clip:12,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Top:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTHC|L'=>'{$Duration:1500,x:0.8,$Clip:3,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Left:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTHC|R'=>'{$Duration:1500,x:-0.8,$Clip:3,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Left:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTHC|B'=>'{$Duration:1500,y:-0.8,$Clip:3,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Top:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'LISTHC|T'=>'{$Duration:1500,y:0.8,$Clip:3,$Easing:$JssorEasing$.$EaseInOutCubic,$ScaleClip:0.8,$Opacity:2,$During:{$Top:[0.4,0.6],$Clip:[0,0.4],$Opacity:[0.4,0.6]}}',
'WV|L'=>'{$Duration:1800,x:0.6,y:0.3,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$Opacity:2,$Round:{$Top:2.5}}',
'WV|R'=>'{$Duration:1800,x:-0.6,y:0.3,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$Opacity:2,$Round:{$Top:2.5}}',
'WV|T'=>'{$Duration:1200,x:-0.2,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:1.5}}',
'WV|B'=>'{$Duration:1200,x:-0.2,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:1.5}}',
'WVC|L'=>'{$Duration:1800,x:0.6,y:0.3,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Top:2.5}}',
'WVC|R'=>'{$Duration:1800,x:-0.6,y:0.3,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$Opacity:2,$Round:{$Top:2.5}}',
'WVC|T'=>'{$Duration:1200,x:-0.2,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:1.5}}',
'WVC|B'=>'{$Duration:1200,x:-0.2,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:1.5}}',
'WVR|L'=>'{$Duration:1800,x:0.6,y:-0.3,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$Opacity:2,$Round:{$Top:2.5}}',
'WVR|R'=>'{$Duration:1800,x:-0.6,y:-0.3,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$Opacity:2,$Round:{$Top:2.5}}',
'JDN|L'=>'{$Duration:2000,x:0.6,y:0.4,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Opacity:2,$Round:{$Top:2.5}}',
'JDN|R'=>'{$Duration:2000,x:-0.6,y:0.4,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Opacity:2,$Round:{$Top:2.5}}',
'JDN|T'=>'{$Duration:1500,x:-0.3,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:1.5}}',
'JDN|B'=>'{$Duration:1500,x:-0.3,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseOutJump,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:1.5}}',
'JUP|L'=>'{$Duration:2000,x:0.6,y:-0.4,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInJump},$Opacity:2,$Round:{$Top:2.5}}',
'JUP|R'=>'{$Duration:2000,x:-0.6,y:-0.4,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInJump},$Opacity:2,$Round:{$Top:2.5}}',
'JUP|T'=>'{$Duration:1500,x:-0.3,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:1.5}}',
'JUP|B'=>'{$Duration:1500,x:-0.3,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Left:1.5}}',
'FADE'=>'{$Duration:900,$Opacity:2}',
'FADE*JDN|L'=>'{$Duration:1200,x:0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic},$Opacity:2}',
'FADE*JDN|R'=>'{$Duration:1200,x:-0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic},$Opacity:2}',
'FADE*JDN|T'=>'{$Duration:1200,x:0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear},$Opacity:2}',
'FADE*JDN|B'=>'{$Duration:1200,x:0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear},$Opacity:2}',
'FADE*JUP|L'=>'{$Duration:900,x:0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'FADE*JUP|R'=>'{$Duration:900,x:-0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic},$Opacity:2}',
'FADE*JUP|T'=>'{$Duration:900,x:-0.6,y:0.6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear},$Opacity:2}',
'FADE*JUP|B'=>'{$Duration:900,x:-0.6,y:-0.6,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear},$Opacity:2}',
'L-JDN'=>'{$Duration:1200,x:0.8,y:0.5,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic},$During:{$Top:[0,0.5]}}',
'R-JDN'=>'{$Duration:1200,x:-0.8,y:0.5,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutCubic},$During:{$Top:[0,0.5]}}',
'T-JDN'=>'{$Duration:1200,x:0.5,y:0.8,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear},$During:{$Left:[0,0.5]}}',
'B-JDN'=>'{$Duration:1200,x:0.5,y:-0.8,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseOutCubic,$Top:$JssorEasing$.$EaseLinear},$During:{$Left:[0,0.5]}}',
'L-JUP'=>'{$Duration:1200,x:0.8,y:-0.5,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic},$During:{$Top:[0,0.5]}}',
'R-JUP'=>'{$Duration:1200,x:-0.8,y:-0.5,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInCubic},$During:{$Top:[0,0.5]}}',
'T-JUP'=>'{$Duration:1200,x:-0.5,y:0.8,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear},$During:{$Left:[0,0.5]}}',
'B-JUP'=>'{$Duration:1200,x:-0.5,y:-0.8,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseLinear},$During:{$Left:[0,0.5]}}',
'L-WVC'=>'{$Duration:1200,x:0.8,y:0.3,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$During:{$Top:[0,0.5]}}',
'R-WVC'=>'{$Duration:1200,x:-0.8,y:0.3,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave},$During:{$Top:[0,0.5]}}',
'T-WVC'=>'{$Duration:1200,x:0.2,y:0.8,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseLinear},$During:{$Left:[0,0.5]}}',
'B-WVC'=>'{$Duration:1200,x:0.2,y:-0.8,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseOutWave,$Top:$JssorEasing$.$EaseLinear},$During:{$Left:[0,0.5]}}',
'L-WVR'=>'{$Duration:1200,x:0.8,y:-0.3,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$During:{$Top:[0,0.5]}}',
'R-WVR'=>'{$Duration:1200,x:-0.8,y:-0.3,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseInWave},$During:{$Top:[0,0.5]}}',
'T-WVR'=>'{$Duration:1200,x:-0.2,y:0.8,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseLinear},$During:{$Left:[0,0.5]}}',
'B-WVR'=>'{$Duration:1200,x:-0.2,y:-0.8,$Opacity:2,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseLinear},$During:{$Left:[0,0.5]}}',
'CLIP-FADE'=>'{$Duration:1200,$Clip:15,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'CLIP|LR-FADE'=>'{$Duration:1200,$Clip:3,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'CLIP|TB-FADE'=>'{$Duration:1200,$Clip:12,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'CLIP|L-FADE'=>'{$Duration:1200,$Clip:1,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'CLIP|R-FADE'=>'{$Duration:1200,$Clip:2,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'CLIP|T-FADE'=>'{$Duration:1200,$Clip:4,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'CLIP|B-FADE'=>'{$Duration:1200,$Clip:8,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'MCLIP|L-FADE'=>'{$Duration:1200,$Clip:1,$Move:true,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'MCLIP|R-FADE'=>'{$Duration:1200,$Clip:2,$Move:true,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'MCLIP|T-FADE'=>'{$Duration:1200,$Clip:4,$Move:true,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'MCLIP|B-FADE'=>'{$Duration:1200,$Clip:8,$Move:true,$Opacity:1.7,$During:{$Clip:[0.5,0.5],$Opacity:[0,0.5]}}',
'L*CLIP'=>'{$Duration:1200,x:0.6,$Clip:12,$Easing:$JssorEasing$.$EaseInCubic,$Opacity:2}',
'R*CLIP'=>'{$Duration:1200,x:-0.6,$Clip:12,$Easing:$JssorEasing$.$EaseInCubic,$Opacity:2}',
'T*CLIP'=>'{$Duration:1200,y:0.6,$Clip:3,$Easing:$JssorEasing$.$EaseInCubic,$Opacity:2}',
'B*CLIP'=>'{$Duration:1200,y:-0.6,$Clip:3,$Easing:$JssorEasing$.$EaseInCubic,$Opacity:2}',
'T-L*'=>'{$Duration:1500,x:0.5,y:0.5,$Rotate:-1,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Left:[0,0.33],$Top:[0.67,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'T-R*'=>'{$Duration:1500,x:-0.5,y:0.5,$Rotate:1,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Left:[0,0.33],$Top:[0.67,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'B-L*'=>'{$Duration:1500,x:0.5,y:-0.5,$Rotate:-1,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Left:[0,0.33],$Top:[0.67,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'B-R*'=>'{$Duration:1500,x:-0.5,y:-0.5,$Rotate:-1,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Left:[0,0.33],$Top:[0.67,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'L-T*'=>'{$Duration:1500,x:0.5,y:0.5,$Rotate:-1,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Left:[0.67,0.33],$Top:[0,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'L-B*'=>'{$Duration:1500,x:-0.5,y:-0.5,$Rotate:-1,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Left:[0.67,0.33],$Top:[0,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'R-T*'=>'{$Duration:1500,x:-0.5,y:0.5,$Rotate:-1,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Left:[0.67,0.33],$Top:[0,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'R-B*'=>'{$Duration:1500,x:-0.5,y:-0.5,$Rotate:-1,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Left:[0.67,0.33],$Top:[0,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'FADE-L*'=>'{$Duration:1500,x:0.5,$Rotate:6.25,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Left:[0,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'FADE-R*'=>'{$Duration:1500,x:-0.5,$Rotate:6.25,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Left:[0,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'FADE-T*'=>'{$Duration:1500,y:0.5,$Rotate:6.25,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Top:[0,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
'FADE-B*'=>'{$Duration:1500,y:-0.5,$Rotate:6.25,$Easing:$JssorEasing$.$EaseLinear,$Opacity:2,$During:{$Top:[0,0.33],$Rotate:[0,0.33]},$Round:{$Rotate:0.25}}',
);
}
?>
