<?php
/*
Plugin Name:Vozeal 
Plugin URI: http://www.vozeal.com/
Description: Boost your audience engagement! Get related videos from within your own website and YouTube Channel! 
Version: 1.0
Author:Vozeal 
Author URI: http://www.vozeal.com
*/

if(!class_exists('InTube'))
{
	class InTube
	{
    public static $optembedwidth = 640;
    public static $optembedheight = 390;
    public static $defaultheight = 640;
    public static $defaultwidth = 390;
    public static $ytregex = '@^\s*https?://(?:www\.)?(?:youtube.com/watch\?|youtu.be/)([^\s"]+)\s*$@im';
    public static $videoCount  = 0;
    public static $videoArray = array();
	/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
        	// Initialize Settings
            require_once(sprintf("%s/settings.php", dirname(__FILE__)));
            $InTube_Settings = new InTube_Settings();
         // Call main function
            //add_action('admin_init',array($this,'onActivate'));
	
            register_activation_hook(__FILE__, array('InTube','onActivate' ));
            add_filter('the_content', 'InTube::youtube_non_oembed', 1);
            wp_embed_register_handler('intube_embed', self::$ytregex, 'InTube::youtube_embed_handler', 1);
	    add_action('activated_plugin','InTube::save_error');
		} // END public function __construct
	 public function onActivate() {
	     InTube::video_sitemap_loop();
	     //error_log("onActivate ".$st);
 	  } 
	public  function save_error(){
  	  update_option('plugin_error',  ob_get_contents());
	}
  

   public function youtube_non_oembed($content) {
	$findIframe = '/<iframe.*?width="(.*?)".*?height="(.*?)".*?src=".*?youtube.com\/embed\/(.{11}?).*?".*?<\/iframe>/si';
	$content = preg_replace_callback($findIframe,"InTube::replaceIframe",$content);
	if (strpos($content, 'httpv://') !== false){
	    $findv = '@^\s*http[vh]://(?:www\.)?(?:youtube.com/watch\?|youtu.be/)([^\s"]+)\s*$@im';
            $content = preg_replace_callback($findv, "InTube::httpv_convert", $content);
        }
        return $content;
   }
	public static function replaceIframe($m)
    {
	$authKey = get_option('auth_key','generic');
	$tags = wp_get_post_tags(get_the_ID());
	$yid = $m[3];
	$width = $m[1];
	$height = $m[2];
	InTube::update_video_record($yid,$authKey,get_permalink(),FALSE,$tags);
	return InTube::embed_video($yid,InTube::$videoCount,$width,$height); 
    }
   public static function httpv_convert($m)
    {
        return self::youtube_embed_handler($m, '', $m[0], '');
    }
   public static function get_aspect_height($url)
    {

        // attempt to get aspect ratio correct height from oEmbed
        $aspectheight = round((self::$defaultwidth * 9) / 16, 0);
        if ($url)
        {
            require_once( ABSPATH . WPINC . '/class-oembed.php' );
            $oembed = _wp_oembed_get_object();
            $args = array();
            $args['width'] = self::$defaultwidth;
            $args['height'] = self::$optembedheight;
            $args['discover'] = false;
            $odata = $oembed->fetch('http://www.youtube.com/oembed', $url, $args);

            if ($odata)
            {
                $aspectheight = $odata->height;
            }
        }

        //add 30 for YouTube's own bar
        return $aspectheight + 30;
    }
   public static function init_dimensions($url = null)
    {

        // get default dimensions; try embed size in settings, then try theme's content width, then just 480px
        if (self::$defaultwidth == null)
        {
            self::$optembedwidth = intval(get_option('embed_size_w'));
            self::$optembedheight = intval(get_option('embed_size_h'));

            global $content_width;
            if (empty($content_width))
                $content_width = $GLOBALS['content_width'];

            self::$defaultwidth = self::$optembedwidth ? self::$optembedwidth : ($content_width ? $content_width : 480);
            self::$defaultheight = self::get_aspect_height($url);
        }
    }
   public function youtube_embed_handler($matches, $attr, $url, $rawattr) {
        self::init_dimensions($url);

        $epreq = array(
            "height" => self::$defaultheight,
            "width" => self::$defaultwidth,
            "vars" => "",
            "standard" => "",
            "id" => "ep" . rand(10000, 99999)
        );

        $ytvars = array();
        $matches[1] = preg_replace('/&amp;/i', '&', $matches[1]);
        $ytvars = preg_split('/[&?]/i', $matches[1]);


        // extract youtube vars (special case for youtube id)
        $ytkvp = array();
        foreach ($ytvars as $k => $v)
        {
            $kvp = preg_split('/=/', $v);
            if (count($kvp) == 2)
            {
                $ytkvp[$kvp[0]] = $kvp[1];
            }
            else if (count($kvp) == 1 && $k == 0)
            {
                $ytkvp['v'] = $kvp[0];
            }
        }


        // setup variables for creating embed code
        $epreq['vars'] = 'ytid=';
        $epreq['standard'] = 'http://www.youtube.com/v/';
        if ($ytkvp['v'])
        {
            $epreq['vars'] .= strip_tags($ytkvp['v']) . '&amp;';
            $epreq['standard'] .= strip_tags($ytkvp['v']) . '?fs=1&amp;';
        }
	$width = $ytkvp['width'];
	$height = $ytkvp['height'];
	error_log("width - $width , height - $height");
        //$epreq['vars'] .= 'rs=w&amp;';
	$authKey = get_option('auth_key','generic');
        error_log("video id: ". $epreq['standard']);
        $ytidmatch = explode('/',$epreq['standard']);
        $yid = explode('?',$ytidmatch[4]);
	$tags = wp_get_post_tags(get_the_ID());
        InTube::update_video_record($yid[0],$authKey,get_permalink(),FALSE,$tags);
        return InTube::embed_video($yid[0],InTube::$videoCount,$width,$height);
        //return self::get_embed_code($epreq);
   }

   function embed_video($vid,$count,$width = 640,$height = 360) {
     error_log("Embedding Video Id: ".$vid);
	$tags = wp_get_post_tags(get_the_ID());
	$authKey = get_option('auth_key','generic');
    	     $height = $height == ''? 360:$height;
     $width = $width == '' ? 640:$width;

	 wp_enqueue_script('jquery');
     wp_enqueue_script('suggestions',plugins_url()."/vozeal/js/suggestions.js");
     wp_enqueue_script('videoControls',plugins_url().'/vozeal/js/ifameVideoControls.js');
     wp_enqueue_style('bootstrapCss',plugins_url().'/vozeal/css/bootstrap-combined.min.css');
     wp_enqueue_style('intubeCss',plugins_url().'/vozeal/css/intube.css');		
     wp_enqueue_script('bootstrapJs',plugins_url().'/vozeal/js/bootstrap.min.js');
     wp_enqueue_script('videoLoad',plugins_url().'/vozeal/js/videoControlsIntube.js');
     wp_enqueue_style('jqueryUiCss',plugins_url().'/vozeal/css//jquery-ui.css');
     wp_enqueue_script('jquery-ui-core');
     InTube::$videoArray[$count] = $vid;
     wp_localize_script('videoLoad','videosArray',InTube::$videoArray);
     wp_localize_script('videoLoad','videoWidth',$width);
     wp_localize_script('videoLoad','videoHeight',$height);
     wp_localize_script('videoLoad','authKey',$authKey);
     $output = '
<div class = "ytPlayerContainer">
<div id="ytplayer'.$count.'" class="ytPlayer"></div>';
       $output = $output.'<div id="suggestionsContainer'.$count.'" class = "suggestions" style="cursor:pointer;display:none">
         </div></div>';
	error_log("plugin error:".get_option('plugin_error'));
     InTube::$videoCount++;
     return $output;
   }
   function video_EscapeXMLEntities($xml) {
      return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $xml);
    }

    private function update_video_record($yt_id,$authKey,$link,$is_channel,$tags) {
	$tagString ='';
        foreach($tags as $tag){
                $tagString = $tag->name.','.$tagString;
        }
	$url = 'http://www.vozeal.com/intube-server/setSuggestions.php';

	//what post fields?
	$fields = array('yt_id' => $yt_id , 'authKey' => $authKey, 'link' => $link,'is_channel' => $is_channel,'tags' => $tagString);

	//build the urlencoded data
	$postvars='';
	$sep='';
	foreach($fields as $key=>$value) 
	{ 
  	$postvars.= $sep.urlencode($key).'='.urlencode($value); 
 	  $sep='&'; 
	}


//open connection
if(function_exists('curl_version')){
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POST,count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);
} 
	/* $url = 'http://54.213.101.133/intube-server/setSuggestions.php';
      $data = array('yt_id' => $yt_id , 'authKey' => $authKey, 'link' => $link,'is_channel' => $is_channel,'tags' => $tagString);

      // use key 'http' even if you send the request to https://...
       $options = array(
           'http' => array(
                   'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                           'method'  => 'POST',
                                   'content' => http_build_query($data),
                                       ),
                                       );
                                       $context  = stream_context_create($options);
                                       $result = file_get_contents($url, false, $context);
                                       error_log($result);
*/
    }
    private function video_sitemap_loop() {
      global $wpdb;
      $posts = $wpdb->get_results ("SELECT id, post_title, post_content, post_date_gmt, post_excerpt 
      FROM $wpdb->posts WHERE post_status = 'publish' 
      AND (post_type = 'post' OR post_type = 'page')
      AND post_content LIKE '%youtube.com%' 
      ORDER BY post_date DESC");

      if (empty ($posts)) {
          error_log("Empty posts");
          return false;

      } else {

        $videos = array();
    
        foreach ($posts as $post) {
            $c = 0;
            if (preg_match_all ("/youtube.com\/(v\/|watch\?v=|embed\/)([a-zA-Z0-9\-_]*)/", $post->post_content, $matches, PREG_SET_ORDER)) {

                    $excerpt = ($post->post_excerpt != "") ? $post->post_excerpt : $post->post_title ; 
                    $permalink = InTube::video_EscapeXMLEntities(get_permalink($post->id)); 
		$tags = wp_get_post_tags($post->id);
		$authKey = get_option('auth_key','generic');
                foreach ($matches as $match) {
                        $id = $match [2]; //youtube id 
                        $fix =  $c++==0?'':' [Video '. $c .'] ';
                        if (in_array($id, $videos))
                            continue;
                        array_push($videos, $id);
                        $uid = $authKey;
                        $is_channel = "0";
                        InTube::update_video_record($id,$uid,$permalink,$is_channel,$tags);
                        $thumbnail = "http://i.ytimg.com/vi/$id/hqdefault.jpg</video:thumbnail_loc>";
                        $title = htmlspecialchars($post->post_title) . $fix;
                        $description = $fix . htmlspecialchars($excerpt);
                        $pub_date = date (DATE_W3C, strtotime ($post->post_date_gmt));
                        $posttags = get_the_tags($post->id); if ($posttags) { 
                        $tagcount=0;
                        foreach ($posttags as $tag) {
                          if ($tagcount++ > 32) break;
                          $tags .= $tag->name;
                        }
                 }
                    $postcats = get_the_category($post->id);

                }
            }
        }

    }
  } //END function loop
		/**
		 * Activate the plugin
		 */
		public static function activate()
		{
      
			// Do nothing
		} // END public static function activate
	
		/**
		 * Deactivate the plugin
		 */		
		public static function deactivate()
		{
			// Do nothing
		} // END public static function deactivate
 
	} // END class InTube
} // END if(!class_exists('InTube'))

if(class_exists('InTube'))
{
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('InTube', 'activate'));
	register_deactivation_hook(__FILE__, array('InTube', 'deactivate'));

	// instantiate the plugin class
	$intube = new InTube();
	
    // Add a link to the settings page onto the plugin page
    if(isset($intube))
    {
        // Add the settings link to the plugins page
        function plugin_settings_link($links)
        { 
            $settings_link = '<a href="options-general.php?page=intube">Settings</a>'; 
            array_unshift($links, $settings_link); 
            return $links; 
        }

        $plugin = plugin_basename(__FILE__); 
        add_filter("plugin_action_links_$plugin", 'plugin_settings_link');
    }
}
