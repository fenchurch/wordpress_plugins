<?php
/*
Plugin Name: Multi Twitter User Feed Widget
Plugin URI: https://github.com/fenchurch/wordpress_plugins/  
Description: Twitter feed using javascript
Version: 0.0.0
Author: Rusty Gibbs
Author URI:http://www.wickedidol.com
License: GPL

This software comes without any warranty, express or otherwise, and if it
breaks your blog or results in your cat being shaved, it's not my fault.

*/
class widgetTwitterM extends WP_Widget{
	private $o = array(
		"id"		=> "twitter",
		"description"	=> "User Feed via json"
	);
	//For some reason, checkboxes need to be unchecked
	private $defaults = array(
		'screen_name'	=> "rustygibbs",
		'title' 	=> "Twitter Updates",
		'count' 	=> '5'
	);
	private $checkboxes = array("show_screen_name", "show_profile_image", "show_real_name", "show_description", "exclude_replies", "show_only_on_home");
	function __construct()
	{
		extract($this->o);
		//We need jquery for this so load to the head
		wp_enqueue_script('jquery');
		wp_enqueue_script('wi_twitter', plugins_url("", __FILE__)."/twitter.js");
		parent::__construct( $id, __(ucfirst($id)), array("description"=>$description, "class"=>$id));
	}
	function widget( $args, $instance)
	{
		extract($args, EXTR_SKIP);		
		$title = (empty($instance['title']) 
			? __($this->defaults['title']) 
			: $instance['title']
		);
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		//open widget
		//add title if present
		echo $before_widget;
		if(! empty($title)) 
			echo $before_title.$title.$after_title;
		
		if(!$instance['show_only_on_home'] || is_home()):
		//body of widget
		?>
			<div class='head'></div>
			<div class='body'>Twitter Loading...</div>
			<script type='text/javascript'>
			<?php /*This is the call to the plugin*/ ?>			
			(function($){ $("#<?php echo $widget_id;?>").twitter(<?php echo json_encode($instance);?>); })(jQuery);
			</script>
		<?php
		endif;
		//close the widget
		echo $after_widget;
	}
	//Form Function
	function form($instance)
	{
		//Set the properties
		foreach( wp_parse_args( (array) $instance, array_merge($this->defaults, array_fill_keys($this->checkboxes, "")))
		as $k => $v){
			$id = $this->get_field_id($k);
			$label = __(ucfirst(str_replace("_", " ", $k)));
			$cb = in_array($k, $this->checkboxes);
			$input = sprintf("<input id='$id' name='".$this->get_field_name($k)."' %s/>", $cb 
				? "type='checkbox' value='1' ".($v==="1"?"checked='checked'":"")
				: "type='text' value='".esc_attr($v)."' class='widefat'");
			printf("<p><label for='$id'>%s<label></p>", ($cb ? $input.$label : $label.$input));
		}
	}
}
add_action('widgets_init', function(){
	register_widget('widgetTwitterM');
});
?>