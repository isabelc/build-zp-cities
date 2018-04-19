function startWorking(){
	wd=window.setInterval('workingDots()',800);
}
function stopWorking(){
	window.clearInterval(wd);
}
function workingDots(){
	var elW = document.getElementById("working");
	if (typeof i === 'undefined') i = 0;
	i = ++i % 4;
	elW.innerText = "Working "+Array(i+1).join(".");
}
function notice(msg,status){
	var el = document.getElementById("notices");
    status = status || "success";
	elChild = document.createElement("div");
	elChild.className = status + " notice-text";
	elChild.innerText = msg;
	el.appendChild(elChild);
	scrollTo(document.body, 0, 100);
}

/* Replace button with "Complete" */
function hideButton(button){
	if (typeof button === 'undefined')
		return;

	var td = document.getElementById(button.id + "-control");
	elChild = document.createElement("span");
	elChild.className = "okay";
	elChild.innerText = '\u2713 Complete';
	button.remove();
	td.appendChild(elChild);
}

window.onload = function(){

	/****************************************************
	*
	* BEGIN buttons
	*
	****************************************************/
	
	var elW = document.getElementById("working");
	elW.style.visibility = 'hidden';	
	 
	var cdButtons = [].slice.call(document.getElementsByClassName("cd-button"));
	if (cdButtons.length > 0) {

		cdButtons.forEach(function (button){
		  button.addEventListener("click", function(e){

		  	e.preventDefault();
			button.disabled = true;
	    	var bspinner = document.getElementById(button.id + "-spinner");
      		bspinner.classList.add("spinner");			
			// remove prior notice
			var notices = document.getElementsByClassName("notice-text");
			if (notices.length > 0) {
				notices[0].remove();
			}
	    	startWorking();
	    	elW.style.visibility = 'visible';

		    if (button.id.startsWith('import-geonames')) {

		    	// Download Buttons

    		    // get filename from button
	    		var gnFilename = button.value;

	    		/****************************************************
	    		* 
	    		* BEGIN 1st ajax request
	    		* 
	    		****************************************************/

				var xhr = new XMLHttpRequest();
				xhr.open('GET', 'ajax/ajax-download.php?f=' + gnFilename);
				xhr.onload = function() {
					elW.style.visibility = 'hidden';
					button.disabled = false;
					bspinner.classList.remove("spinner");
					stopWorking();
				    if (xhr.status === 200) {
				    	
						var r = JSON.parse(xhr.responseText);

				    	if ('working' == r.status) {

				    		// kick off another request to retry download.

				    		/****************************************************
				    		* 
				    		* BEGIN nested ajax request (Request #2)
				    		* 
				    		****************************************************/
				    		button.disabled = true;
				    		bspinner.classList.add("spinner");
							startWorking();
			    			elW.style.visibility = 'visible';
							var xhr2 = new XMLHttpRequest();
							xhr2.open('GET','ajax/ajax-download.php?f=' + gnFilename + '&retry=1');
							xhr2.onload = function() {
								elW.style.visibility = 'hidden';
								button.disabled = false;
								bspinner.classList.remove("spinner");
								stopWorking();

								if (xhr2.status === 200) {
									var r2 = JSON.parse(xhr2.responseText);
									notice(r2.message,r2.status);
									if ('success' == r2.status || 'info' == r2.status) {
										hideButton(button);
									}
									
								} else {
				        			notice('Request #2 failed. Returned status of ' + xhr2.status, 'error');
				    			}
							};
							xhr2.send();

				    		/****************************************************
				    		* 
				    		* END nested ajax request (Request #2)
				    		* 
				    		****************************************************/

				    	} else {
				    		notice(r.message,r.status);
							if ('success' == r.status || 'info' == r.status) {
								hideButton(button);
							}

				    	}

				        
				    } else {
				        notice('Request failed. Returned status of ' + xhr.status, 'error');
				    }
				};
				xhr.send();


	    		/****************************************************
	    		* 
	    		* END 1st ajax request
	    		* 
	    		****************************************************/



		    } // END Download Buttons

		    else {

				var xhr = new XMLHttpRequest();
				xhr.open('GET', 'ajax/ajax-' + button.id + '.php?v=' + button.value);
				xhr.onload = function() {
					elW.style.visibility = 'hidden';
					button.disabled = false;
					bspinner.classList.remove("spinner");
					stopWorking();
				    if (xhr.status === 200) {
				    	var r = JSON.parse(xhr.responseText);
			    		notice(r.message,r.status);
						if ('success' == r.status || 'info' == r.status) {
							hideButton(button);
						}
				    } else {
				        notice('Request failed. Returned status of ' + xhr.status, 'error');
				    }
				};
				xhr.send();

		    } // end all other buttons


		  },false);

		}); // end foreach

		
	} // end if (cdButtons.length > 0)

	/****************************************************
	*
	* END buttons
	*
	****************************************************/

};
