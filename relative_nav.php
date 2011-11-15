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
		extract($args);
		extract($opt = get_option('relative_nav'));
		
		$items = widget_relative_nav_data($post->ID, $post->post_type);
		if(!$items){
			$post = get_post($post->post_parent);
			$items = widget_relative_nav_data($post->ID, $post->post_type);
		}
		$widgetFmt = "$before_widget$before_title$title".'%1$s'."$after_title\n".'%2$s'."\n$after_widget\n";
		$label="";
		if($parent){
			$label = sprintf('<a href="%2$s">%1$s</a>', get_the_title($post->ID),get_permalink($post->post_parent));
			if($decendants){
				$label.= "<div class='rents'><ul>";
				foreach( get_post_ancestors($post->ID) as $v) $label.= widget_relative_nav_li($v);
				$label.= "</ul></div>";
			}
		}
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
				var down = function(e){$(e.target).text("-").unbind("click").click(up).next(".children").slideDown();};
				var up = function(e){$(e.target).text("+").unbind("click").click(down).next(".children").slideUp();};
				$("#relative_nav div").click(down);
			});
		</script>
		<?php
	}
	function widget_relative_nav_li($id, $liClass="", $afterA="", $beforeA=""){
		return sprintf('<li%3$s>%5$s<a href="%2$s">%1$s</a>%4$s</li>', get_the_title($id), get_permalink($id), $liClass, $afterA, $beforeA);
	}
	function widget_relative_nav_data($id, $type=null, $oid=null, $r=""){
		if(!$oid) $oid = $id;
		if(!$r) $r = "";
		$expander = "<div>+</div>";
		$data = get_children(array("post_parent"=>$id, "post_type"=>$type, 'order'=> 'ASC'));
		if($data){
			$d = "";
			foreach($data as $k => $v){
				$d.= widget_relative_nav_li(
					$v->ID,
					" class=''",
					widget_relative_nav_data(
						$v->ID, 
						$type,
						$oid, 
						$r
					)
				);
			}
			$r.= ($oid != $id?$expander:"")."<ul".($oid != $id? " class='children'":"").">$d</ul>";
		}
		return $r;
	}
	wp_register_sidebar_widget( $id, $label, $call, array('description'=>$desc));
	wp_register_widget_control( $id, $label, $ctrl, array('description'=>$desc));
}

// */ // Run code and init
add_action('widgets_init', 'widget_relative_nav_init');
?>
<?php
/*
Plugin Name: Relative Pages Menu
Plugin URI: 
Description: Adds a menu that is relative to the current page's heirarchy 
Version: 0.0.0
Author: Rusty Gibbs
Author URI: http://wickedidiol.com/
License: GPL

This software comes without any warranty, express or otherwise, and if it
breaks your blog or results in your cat being shaved, it's not my fault.

function widget_relative_nav_init() {

	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_relative_nav($args) {
//		$_widget = "widget_relative_nav";
		// "$args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys." - These are set up by the theme
		extract($args);
		// These are our own options
		$options = get_option($widget_id);
		$title = $options['title'];  // Title in sidebar for widget
		// Output 
		// Gotta retreive the post data
		//if(is_single_page()){
		global $post;
		if(! widget_relative_nav_has_kids($post)){
			$oid = $post->ID;			
			$post = (get_page($post->post_parent));
		}
		
		echo $before_widget;
		if($title)
			echo $before_title.$title.$after_title;
		widget_relative_nav_head($post, $widget_id);
		widget_relative_nav_kids($post, null, $oid);
		echo $after_widget;
		//}
	}
	function widget_relative_nav_has_kids($post){
		if(count( get_pages(array( 'parent' => $post->ID, 'hierarchical' =>0)))) return true;
		else return false;
	}
	function widget_relative_nav_head($post, $widget_id){
		//Javascript for toggling up and down
		$options = get_option($widget_id);
		?>
		<script type='text/javascript'>
			$(function(){
				var down = function(e){$(e.target).text("-").unbind("click").click(up).next().slideDown();};
				var up = function(e){$(e.target).text("+").unbind("click").click(down).next().slideUp();};
				$("#<?=$widget_id?> div").click(down);
			});
		</script>
		<h2>
		<?php if($post->post_parent && $options['parent']):?>
			<a href='<?php echo get_permalink($post->post_parent);?>'><?= $post->post_title?></a>
		<?php else:?>
			<?= $post->post_title?>
		<?php endif;?>
		</h2>
		<?php
		
	}
	function widget_relative_nav_kids($id, $oid = null, $sel = null){
		//Set $oid as the root to the kids
		if(!$oid) $oid = $id;
		$f = '%1$s<ul>%2$s<li%3$s><a href="%4$s">%5$s</a>%6$s</li></ul>';
		/*$kids =get_pages(array( 
			'sort_column' => 'menu_order', 
			'child_of' => 0,
			'parent' => $id, 
			'hierarchical' =>0 
		));
		if(count( $kids )){
		//	$args = array( $id!=$oid?"<div>+</div>":"" );
	
		//	foreach($kids as $k => $v)
		//		array_merge( $args, array( ($v->ID == $sel?" class='sel'":""),get_page_link($v->ID),$v->post_title,widget_relative_nav_kids($v,$oid)));
			
		//	vprintf($f, $args);
		}
	} 
	// Settings form
	function widget_relative_nav_control() {
		//global $id;
		$w = "relative_hierarchical_nav";
		// Get options
		$options = get_option($w);
		// options exist? if not set defaults
		if ( !is_array($options) )
			$options = array('title'=>'', 'parent' => 1);

        // form posted?
		if ( $_POST[$w.'-submit'] ) {

			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST[$w.'-title']));
			$options['parent'] = $_POST[$w.'-parent'];
			update_option($w, $options);
		}

		// Get options for form fields to show
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$parent = $options['parent'];
		
		// The form fields
		?>
		<p>
			<label for="<?=$w;?>-title"><?php echo __('Title:');?></label>
			<input id="<?=$w;?>-title" name="<?=$w?>-title" type="text" value="<?=$title?>" />
		</p><p>
			<label for="<?=$w;?>-parent"><?php echo __('Head leads to Parent:');?></label>
			<input id="<?=$w;?>-parent" name="<?=$w?>-parent" type="checkbox" value="1" <?php if($parent) echo "checked='checked'";?>/>
		</p><p>
			<input type="hidden" id="<?=$w;?>-submit" name="<?=$w?>-submit" value="1" />
		</p>
		<?php
	}


	// Register widget for use
	wp_register_sidebar_widget(
		"relative_hierarchical_nav",
		"Relative Hierarchy Navigation",
		"widget_relative_nav", 
		array('description'=>"Navigation for Page Hierarchy relative to current page")
	);
	// Register settings for use, 300x200 pixel form
	wp_register_widget_control(
		"relative_hierarchical_nav",
		"Relative Hierarchy Navigation",
		"widget_relative_nav_control", 
		array('description'=>"Navigation for Page Hierarchy relative to current page")
	);
		//array('Relative Hierarchical Nav', 'widgets'), 'widget_relative_nav_control');
}

// Run code and init
add_action('widgets_init', 'widget_relative_nav_init');
*/

?>
