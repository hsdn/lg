$(document).ready(function(){
    var url = "index.php"+$("#mapa").attr("url");
	
	$.post(url, "", function(data){

		$("#mapa").html(data);
		$("#loading").hide();
		
	});
	
});