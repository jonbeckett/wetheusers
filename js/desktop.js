
$(document).ready(function(){


  
	// $(".tiles").freetile({ animate: true, elementDelay: 30 });

	$(".tiles").isotope({
		layoutMode:'masonry',
		masonry: { columnWidth:330, isFitWidth:true }
	});
		
	$("#tags_page > .tags").freetile({ animate: true, elementDelay: 30 });
	
	$(".directory_users").isotope({
		layoutMode:'masonry',
		masonry: { columnWidth:330, isFitWidth:true }
	});
		
	$(".tiles").find("iframe").each(function(){
		var w = $(this).attr("width");
		var h = $(this).attr("height");
		
		var ratio = h/w;
		var new_w = 280;
		var new_h = Math.round(280 * ratio);
		
		$(this).attr("width",new_w);
		$(this).attr("height",new_h);
		
	});
	
	$(".posts").find("iframe").each(function(){
		var w = $(this).attr("width");
		var h = $(this).attr("height");
		
		var ratio = h/w;
		var new_w = 500;
		var new_h = Math.round(500 * ratio);
		
		$(this).attr("width",new_w);
		$(this).attr("height",new_h);
		
	});
	
	// force a re-layout after a short delay
	window.setTimeout(function(){
		$(".tiles").isotope('layout');
		$(".directory_users").isotope('layout');
		
		},1000);
	

	
});
