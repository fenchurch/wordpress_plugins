<?php
/*
Plugin Name: Relative Navigation
Plugin URI: https://github.com/fenchurch/wordpress_plugins/  
Description: Navigation elements for going up and down the ancestory. Multi generational children list and decendant list
Version: 0.0.0
Author: Rusty Gibbs
Author URI:http://www.wickedidol.com
License: GPL

This software comes without any warranty, express or otherwise, and if it
breaks your blog or results in your cat being shaved, it's not my fault.
*/
function widget_relative_nav_init(){

	$id = "relative_nav";
	$label = "Relative Navigation`";
	$desc = "Navigation elements for going up and down the ancestory. Multi generational children list and decendant list";
	$call = "widget_relative_nav";
	$ctrl = "widget_relative_nav_ctrl";

	if ( !function_exists('register_sidebar_widget') ) return;
	//Widget
	function widget_relative_nav($args){
		global $post;
		//Default args, $before/after_widget, $before/after_title
		extract($args);
		//Widget options from control
		extract($opt = get_option('relative_nav'));
		//
		$widgetFmt = $before_widget."\n".$before_title."\n".$title."\n".$after_title."\n".
			'<div class="rents">%1$s</div>'."\n".
			'<div class="kids">%2$s</div>'."\n".
			"\n".$after_widget;
		$label = "";

		$items = widget_relative_nav_data($post->ID, $post->post_type);
		//If no kids, use the parent and list
		if(!$items){
			$id = $post->ID;
			$post = get_post($post->post_parent);
			$items = widget_relative_nav_data($post->ID, $post->post_type, $id);
		}
		if($parent){
			$label.= sprintf('<a href="%2$s">%1$s</a>', get_the_title($post->ID),get_permalink($post->post_parent));
			if($decendants){
				$l = "";
				foreach( get_post_ancestors($post->ID) as $v) $l.= widget_relative_nav_li($v);
				$label.= "<div><ul>$l</ul></div>";
			}
		}
	//	print $widgetFmt;
		printf($widgetFmt, $label, $items);
		widget_relative_nav_script();
	}
	//Controller
	function widget_relative_nav_ctrl(){
		//Add if checkbox
		$checkboxes = array("parent", "decendants");
		//overwrite option label with kv pair;
		$labels = array("parent"=>"Show the parent", 'decendants' => "Show all decendants");
		
		//Options?defaults
		$opt = get_option($o = 'relative_nav');
		if (!is_array($opt)) $opt = array('title'=>'', 'parent' => 1, 'decendants' => 1);
		extract($opt);
		
		//If form posted, set options
		if (isset($_POST["relative_nav-submit"])) foreach($opt as $k => $v){
			$opt[$k] = strip_tags(stripslashes($_POST[$k]));
			update_option($o, $opt);
		}
		
		//formatter string for form elements
		//%1 = k, %2 = formatted k, %3 = value or ?"checked='checked'":""
		$txt = '<p><label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%1$s" type="text" value="%3$s" /></p>';
		$cb = '<p><input id="%1$s" name="%1$s" type="checkbox" value="1" %3$s /><label for="%1$s">%2$s</label></p>';
		
		//Create form elements for the options
		foreach($opt as $k => $v){
			$v = htmlspecialchars($v, ENT_QUOTES);
			$label = __(ucwords(str_replace("_", " ", (isset($labels[$k])?$labels[$k]:$k))));
			if(in_array($k, $checkboxes))
				printf($cb, $k, $label, ($v?"checked='checked'":""));
			else
				printf($txt, $k, "$label:", $v);
		}
		echo '<p><input type="hidden" id="submit" name="relative_nav-submit" value="1" /></p>';
	}
	function widget_relative_nav_script(){
		?>
		<link rel="stylesheet" href="<?php echo plugins_url("", __FILE__);?>/relative_nav.css" type="text/css" media="all" charset="utf-8" />		
		<script type='text/javascript'>
			$(function(){
				var down = function(e){$(e.target).text("-").unbind("click").click(up).next("ul").slideDown();};
				var up = function(e){$(e.target).text("+").unbind("click").click(down).next("ul").slideUp();};
				$("#relative_nav .expander").click(down);
			});
		</script>
		<?php
	}
	function widget_relative_nav_li($id, $liClass="", $afterA="", $beforeA=""){
		return "\n".sprintf('<li%3$s>%5$s<a href="%2$s">%1$s</a>%4$s</li>', get_the_title($id), get_permalink($id), $liClass, $afterA, $beforeA)."\n";
	}
	function widget_relative_nav_data($id, $type=null, $oid=null, $firstRun = true, $r=""){
		if(!$oid) $oid = $id;
		$expander = ($firstRun?"":"<div class='expander'>+</div>");
		$data = get_children(array("post_parent"=>$id, "post_type"=>$type, 'order'=> 'ASC'));
		if($data){
			$d = "";
			foreach($data as $k => $v)
				$d.= widget_relative_nav_li( $v->ID, ($v->ID == $oid ?" class='sel'":""), widget_relative_nav_data($v->ID, $type, $oid, false, $r));
			$r.= "$expander<ul>$d</ul>";
		}
		return $r;
	}
	wp_register_sidebar_widget( $id, $label, $call, array('description'=>$desc));
	wp_register_widget_control( $id, $label, $ctrl, array('description'=>$desc));
}

// */ // Run code and init
add_action('widgets_init', 'widget_relative_nav_init');
?>
