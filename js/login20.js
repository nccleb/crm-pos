
function quit(){

	
	window.close();

	
}




function size(){
	window.resizeTo(800,750);
}



$(document).ready(function(){
    $("#myBtn").click(function(){
        $("#myModal").modal();
    });
})