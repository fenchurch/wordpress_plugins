/*
Plugin Name: Twitter User Feed Widget
Plugin URI: https://github.com/fenchurch/wordpress_plugins/  
Description: javascript for Twitter User Feed Widget
Version: 1.0.0
Author: Rusty Gibbs
Author URI:http://www.wickedidol.com
License: GPL

This software comes without any warranty.
*/
(function($){
	var plugin = "twitter",
	//Plugin Vars
	api	="https://api.twitter.com/1/statuses/user_timeline.json?",
	apiArgs	=["screen_name","count","exclude_replies"],
	url	="http://twitter.com/",
	defaults={
	//Formatting, head is name,avatar,bio
		head:"div.head",
		body:"div.body"
	},
	//Options
	//Public Methods
	methods = {
		init	:function(options){
			return this.each(function(){
				var $this = $(this);
				$this.o = $.extend({}, defaults, options);
				_m.load.call($this);
			});
		},
		log	:function(options){
			this.o = $.extend({}, defaults, options);
			_m.load(true);
		}
	},
	//Private Methods
	_m = {
		load	:function(log){
			//set up the data from options given using apiArgs
			var 	con = typeof console != "undefined" && typeof console.log != "undefined" ? true : false,
				data = {},
				conlog = function(){if(con) console.log(arguments)};
			for(i in apiArgs) if(this.o[apiArgs[i]]) data[apiArgs[i]] = this.o[apiArgs[i]];

			//Call Ajax to get the feed
			$.ajax({
				context	:this,
				url	:api,
				dataType:"jsonp",
				data	:data,
				error	:conlog,
				complete:conlog,
				beforeSend:conlog,
				fail	:conlog,
				success	:function(r,x){
					if(log && con)
						console.log(r,x);
					if(!log)
						_m.head.call(_m.body.call(this, r), r[0].user);
				}
			});
		},
		head	:function(a)
		{	
			p = this.find(this.o.head);
			l = $("<a />", {href:url+a.screen_name});
			s = "show_";
			if(this.o[s+(k = "profile_image")])
				p.append($("<div />", {
					className:k,
					html:l.clone().html($("<img />", {src:a[k+"_url"]}))
				}));
			if(this.o[s+(k="screen_name")])
				p.append($("<div />", {
					className:k,
					html:l.clone().html("@"+a.screen_name)
				}));
			if(this.o[s+"real_"+(k="name")])
				p.append($("<div />", {className:k, html:a[k]}));
			if(this.o[s+(k = "description")])
				p.append($("<div />", {className:k, html:a[k]}));
			return this;
		},
		body	:function(a)
		{
			var p;
			//Overwrite "twitter loading..." with the UL, make that the rent			
			this.find(this.o.body).html(p = $("<ul />"));
			for(i in a)
			p.append($("<li />", {
				html:
				$("<p />", {html:_m.linkify(a[i].text)}).
				after($("<p />", {
					html:$("<a />", {
						html:_m.timeago(a[i].created_at),
						href:url+a[i].user.screen_name+"/status/"+a[i].id_str,
						title:a.created_at
					})
				}))
			}));
			return this;
		},
		linkify	:function(txt)
		{
		//Stolen from twitter badge
		//modified to take protocol and "@" out of anchor texts
		//Still needs hash tag markup
			return txt.
			replace(/((https?|s?ftp|ssh)\:\/\/)([^"\s\<\>]*[^.,;'">\:\s\<\>\)\]\!])/g, 
			function() { return '<a href="'+arguments[0]+'">'+arguments[3]+'</a>';}).
			replace(/\B@([_a-z0-9]+)/ig,
			function(r) { return '<a href="http://twitter.com/'+r.substring(1)+'">'+r.substring(1)+'</a>';});
		},
		timeago	:function(date)
		{
		//Stolen from twitter badge
			var values 	= date.split(" "),
			parsed_date 	= Date.parse(values[1]+" "+values[2]+", "+values[5]+" "+values[3]),
			relative_to 	= new Date(),
			delta 		= parseInt((relative_to.getTime() - parsed_date) / 1000) + (relative_to.getTimezoneOffset() * 60);
			if (delta < 60) 
				return 'less than a minute ago';
			else if(delta < 120)
				return 'about a minute ago';
			else if(delta < (60*60))
				return (parseInt(delta / 60)).toString() + ' minutes ago';
			else if(delta < (120*60))
				return 'about an hour ago';
			else if(delta < (24*60*60))
				return 'about ' + (parseInt(delta / 3600)).toString() + ' hours ago';
			else if(delta < (48*60*60))
				return '1 day ago';
			else
				return (parseInt(delta / 86400)).toString() + ' days ago';
		}
	}
	//End Variables
	
	//Jquery Constructor
	$.fn[plugin] = function(method){
		var argCount = 0;
		var args = $(arguments).toArray().slice(argCount);
		if(methods[method]){
			return methods[method].apply( this, Array.prototype.slice.call(args, 1));
		}else if(typeof method === 'object' || !method ){
			return methods.init.apply(this, args);
		}else{
			$.error("Method." + method + ' does not exist on $.fn.'+plugin);
		}
	}
	$[plugin] = function(options){
		opt(options);
		return undefined;
	}
})(jQuery);
