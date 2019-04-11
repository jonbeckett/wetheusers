$(document).ready(function(){

	$(".tiles").find("iframe").each(function(){
		var w = $(this).attr("width");
		var h = $(this).attr("height");
		
		var ratio = h/w;
		var new_w = $(".post").css("width");
		
		new_w = parseInt(new_w.substr(0,new_w.length-2));
		
		var new_h = Math.round(new_w * ratio);
		
		$(this).attr("width",new_w);
		$(this).attr("height",new_h);
		
	});
	
	$(".posts").find("iframe").each(function(){
		var w = $(this).attr("width");
		var h = $(this).attr("height");
		
		var ratio = h/w;
		var new_w = $(".body").width();
		var new_h = Math.round(new_w * ratio);
		
		$(this).attr("width",new_w);
		$(this).attr("height",new_h);
		
	});
	
	$(".post").find("IMG").each(function(){
		var w = $(this).attr("width");
		var h = $(this).attr("height");
		
		var ratio = h/w;
		var new_w = $(".body").width();
		var new_h = Math.round(new_w * ratio);
		
		$(this).attr("width",new_w);
		$(this).attr("height",new_h);
		
	});
});