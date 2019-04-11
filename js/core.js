function validateAccountForm(){
	var result = true;

	$("#twitter").css("background-color","#ffffff");
	$("#facebook").css("background-color","#ffffff");
	$("#tumblr").css("background-color","#ffffff");
	$("#googleplus").css("background-color","#ffffff");
	$("#livejournal").css("background-color","#ffffff");
	$("#wordpress").css("background-color","#ffffff");
	$("#blogger").css("background-color","#ffffff");
	
	if ($("#twitter").val().indexOf("twitter.com") > -1) {
		$("#twitter").css("background-color","#ffffaa");
		result = false;
	}
	if ($("#facebook").val().indexOf("facebook.com") > -1) {
		$("#facebook").css("background-color","#ffffaa");
		result = false;
	}
	if ($("#tumblr").val().indexOf("tumblr.com") > -1) {
		$("#tumblr").css("background-color","#ffffaa");
		result = false;
	}
	if ($("#googleplus").val().indexOf("google.com") > -1) {
		$("#googleplus").css("background-color","#ffffaa");
		result = false;
	}
	if ($("#livejournal").val().indexOf("livejournal.com") > -1) {
		$("#livejournal").css("background-color","#ffffaa");
		result = false;
	}
	if ($("#wordpress").val().indexOf("http://") > -1) {
		$("#wordpress").css("background-color","#ffffaa");
		result = false;
	}
	if ($("#blogger").val().indexOf("http://") > -1) {
		$("#blogger").css("background-color","#ffffaa");
		result = false;
	}
	
	if (result == false) alert("Please only submit the username or ID for the social network links, or the domain name only (no http) of URLs - check the fields marked in yellow.");
	
	return result;
}

function checkSubmit(e){
   if(e && e.keyCode == 13)
   {
      $("#search_submit_button").click();
   }
}

$(document).ready(function(){

	$(".like_button").hover(function(){
		$(this).addClass("hover");
	},function(){
		$(this).removeClass("hover");
	});
	
	$(".like_button").click(function(){
		
		var button = this;
		var post_id = $(this).attr("post_id");
		var like_count_id = $(this).attr("like_count_id");
		var method = ($(this).hasClass("liked")) ? "unlike" : "like";
		
		$(button).addClass("waiting");
		
		//alert("http://" + document.domain + "/api/post/" + method + "/" + post_id);
		
		$.ajax({
			type: "POST",
			url: "http://" + document.domain + "/api/post/" + method + "/" + post_id,
			data: {},
			success: function (data) {
				if (Number(data) > -1){
					
					switch(method){
						case "like":
							$(button).removeClass("waiting");
							$(button).addClass("liked");
							break;
						case "unlike":
							$(button).removeClass("waiting");
							$(button).removeClass("liked");
							break;
					}
					$("#" + like_count_id).text(data);
				}
			},
			dataType: "json",
			async:true
		});
	});
	
	$(".comment_like_button").click(function(){
		
		var button = this;
		
		var post_id = $(this).attr("post_id");
		var comment_id = $(this).attr("comment_id");
		var like_count_id = $(this).attr("comment_like_count_id");
		var method = ($(this).hasClass("comment_liked")) ? "unlike" : "like";
		
		$(button).addClass("waiting");
		
		//alert("http://" + document.domain + "/api/comment/" + method + "/" + post_id + "/" + comment_id);
		
		$.ajax({
			type: "POST",
			url: "http://" + document.domain + "/api/comment/" + method + "/" + post_id + "/" + comment_id,
			data: {},
			success: function (data) {
				
				if (Number(data) > -1){
					
					switch(method){
						case "like":
							$(button).removeClass("waiting");
							$(button).addClass("comment_liked");
							break;
						case "unlike":
							$(button).removeClass("waiting");
							$(button).removeClass("comment_liked");
							break;
					}
					$("#" + like_count_id).text(data);
				}
			},
			dataType: "json",
			async:true
		});
	});
	
	// make buttons disable after being clicked
	$('form').submit(function(){
		$(this).children('input[type=submit]').prop('disabled', true);
	});
	
});
