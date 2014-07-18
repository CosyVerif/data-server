/*
$(function(){
	
	// Checking for CSS 3D transformation support
	$.support.css3d = supportsCSS3D();
	
	var formContainer = $('#formContainer');
	
	// Listening for clicks on the ribbon links
	$('.flipLink').click(function(e){
		
		// Flipping the forms
		formContainer.toggleClass('flipped');
		
		// If there is no CSS3 3D support, simply
		// hide the login form (exposing the recover one)
		if(!$.support.css3d){
			$('#login').toggle();
		}
		e.preventDefault();
	});
	
	formContainer.find('form').submit(function(e){
		// Preventing form submissions. If you implement
		// a backend, you might want to remove this code
		e.preventDefault();
	});
	
	
	// A helper function that checks for the 
	// support of the 3D CSS3 transformations.
	function supportsCSS3D() {
		var props = [
			'perspectiveProperty', 'WebkitPerspective', 'MozPerspective'
		], testDom = document.createElement('a');
		  
		for(var i=0; i<props.length; i++){
			if(props[i] in testDom.style){
				return true;
			}
		}
		
		return false;
	}
});
*/

rootURL = "http://localhost:8080/server.php";

url = "";
username = "";
password = "";
complete_name = "Username";

function loadPage(page)
{
	$('#contenu').load("html/" + page + ".html");
}
function loadMenu(page)
{
	$('#menuGauche #menu').load("html/" + page + ".html");
}

function get(url, successFunction, errorFunction)
{
  jQuery.ajax({
    type: 'GET', // Le type de ma requete
    url: rootURL + url, 
    dataType: 'json',
    beforeSend: function (request)
    	{
    		request.setRequestHeader("Authorization", "Basic  "+ btoa(username + ":" + password) +"==");
    	},
    success: successFunction,
    error: errorFunction
  });
}

function put(url, data, successFunction, errorFunction)
{
  jQuery.ajax({
    type: 'PUT', // Le type de ma requete
    contentType: 'application/json',
    url: rootURL + url, 
    dataType: 'json',
    data : data,
    beforeSend: function (request)
    	{
    		request.setRequestHeader("Authorization", "Basic  "+ btoa(username + ":" + password) +"==");
    	},
    success: successFunction,
    error: errorFunction
  });
}


