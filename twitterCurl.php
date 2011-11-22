<?php
/*
Plugin Name: Twitter via Curl
Plugin URI: https://github.com/fenchurch/wordpress_plugins/  
Description: Feed via Curl
Version: 0.0.0
Author: Rusty Gibbs
Author URI:http://www.wickedidol.com
License: GPL

This software comes without any warranty, express or otherwise, and if it
breaks your blog or results in your cat being shaved, it's not my fault.

*/
function widget_twitterCurl_init(){

	$id = "twitterCurl";
	$label = "Twitter Feed`";
	$desc = "via Curl";
	$call = "widget_twitterCurl";
	$ctrl = "widget_twitterCurl_ctrl";

	if ( !function_exists('register_sidebar_widget') ) return;

	function widget_twitterCurl($args){
		//Twitter Base URL
		$tw = "http://twitter.com/#!/";		
		// "$args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys." - These are set up by the theme
		extract($args);
		// These are our own options
		extract($opt = get_option('widget_twitterCurl'));
		foreach(array("home", "title") as $v) unset($opt[$v]);
		if(!$home || is_home()){
			//String for formatting. %1 = after title, %2 = feed			
			$widgetFmt = "$before_widget$before_title$title".' %1$s'."$after_title<ul>".'%2$s'."</ul>$after_widget";
			$itemFmt = '<li><p>%1$s<br /><a href="%2$s">%3$s</a></p></li>';
			
			//If no screenName, error out				
			if(!$screen_name){
				printf($widgetFmt, "Account Name not set", "");
				return false;
			//Otherwise make screen_name link, maybe put link option in here later
			}
			$screen_name_link = "<a href='$tw$screen_name'>@$screen_name</a>";
			//Get the feed data:
			$data = widget_twitterCurl_data($opt);
			if(!$data){
				printf($widgetFmt, "Error getting Twitter feed", "");
				return false;
			}
			$items = array();
			foreach( $data as $k => $v)
				$items[] = sprintf( $itemFmt,
					widget_twitterCurl_linkify($v['text']), 
					"$tw$screen_name/status/{$v['id_str']}",
					widget_curl_twitter_relativeTime($v['created_at'])
				);
			printf($widgetFmt, $screen_name_link, implode("",$items));			
		}
	}
	function widget_twitterCurl_ctrl(){
			// Get options
		$opt = get_option($o = 'widget_twitterCurl');
		// options exist? if not set defaults
		if ( !is_array($opt) )
			$opt = array(
				'screen_name'=>'rustygibbs', 
				'title'=>'Twitter Updates', 
				'count'=>'5', 
				'exclude_replies' =>'1', 
				'home' =>'1'
			);
        	// form posted?
		if ( $_POST['$o-submit'] ) {
			foreach($opt as $k => $v)
				$opt[$k] = strip_tags(stripslashes($_POST[$k]));
			update_option($o, $opt);
		}
		//%1 = k, %2 = formatted k, %3 = value or ?"checked='checked'":""
		$txt = '<p><label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%1$s" type="text" value="%3$s" /></p>';
		$cb = '<p><input id="%1$s" name="%1$s" type="checkbox" value="1" %3$s /><label for="%1$s">%2$s</label></p>';

		foreach($opt as $k => $v){
			$v = htmlspecialchars($v, ENT_QUOTES);
			$label = __(ucwords(str_replace("_", " ", ($k == "home"?"Only show on Home":$k))));
			//Checkboxes
			if($k == "exclude_replies" || $k == "home")
				printf($cb, $k, $label, ($v?"checked='checked'":""));
			//Otherwise Text Boxes
			else
				printf($txt, $k, $label.":", $v);
		}
		//Hidden
		echo '<p><input type="hidden" id="$o-submit" name="$o-submit" value="1" /></p>';
	}
	function widget_twitterCurl_data($args){
	//Get the data as json
		$url = "http://api.twitter.com/1/statuses/user_timeline.json?".http_build_query($args);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
		$data = curl_exec($ch);
		curl_close($ch);	
		return ($data === false)
			?false
			:json_decode($data, true);
	}
	function widget_twitterCurl_linkify($data) {
		return preg_replace("/#(\w+)/", "<a href='http://search.twitter.com/search?q=$1' target='_blank'>#$1</a>",
			preg_replace("/@(\w+)/", "<a href='http://www.twitter.com/$1\' target='_blank'>$1</a>",
			preg_replace("#((https?|s?ftp|ssh)\:\/\/([\w\/\.]+))#", "<a href='$1' target='_test'>$3</a>",
			preg_replace("#[\s\n\r]([\w]+\.[\w{2-4}]+[\w\/]+)#", " http://$1",
			$data))));
	}
	function widget_curl_twitter_relativeTime($time, $suf = " ago") {
		$t0 = date_parse($time);
		$t1 = date_parse(date("Y-m-d H:i:s"));
		$r = "";
		foreach(array("year", "month", "day", "hour", "minute", "second") as $k){
			if($t0[$k] != $t1[$k]){
				$c = $t1[$k] - $t0[$k];
				$r = $c.($c==1 ? " $k": " {$k}s").$suf;
				break;
			}
		}
		if($r)	return $r;
		else	return "just now";

	}
	wp_register_sidebar_widget( $id, $label, $call, array('description'=>$desc));
	wp_register_widget_control( $id, $label, $ctrl, array('description'=>$desc));
}

// Run code and init
add_action('widgets_init', 'widget_twitterCurl_init');
?>
