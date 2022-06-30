	function showDbMenu() {
		document.getElementById("dbMenu").classList.toggle("show");
	}

	// Close the dropdown if the user clicks outside of it
	window.onclick = function(event) {
	  if (!event.target.matches('.dropBtn')) {
		var dropdowns = document.getElementsByClassName("dbMenu-content");
		var i;
		for (i = 0; i < dropdowns.length; i++) {
		  var openDropdown = dropdowns[i];
		  if (openDropdown.classList.contains('show')) {
			openDropdown.classList.remove('show');
		  }
		}
	  }
	}

	document.addEventListener("DOMContentLoaded", function(event) {
		document.getElementById("defaultOpenTab")?.click();
		for(const table of document.querySelectorAll('#ajaxedRows')){
			for(const loadWorksButton of table.querySelectorAll('.add-row')){
				loadWorksButton.addEventListener("click", async (e) => {
					thisRow = loadWorksButton.closest('tr');
					nextRowIndex = thisRow.rowIndex + 1;
					if(table.rows[nextRowIndex] && table.rows[nextRowIndex].classList.contains('addedRow')){			
						table.deleteRow(nextRowIndex);
					}else{
						var responseText = await loadUrl('ajax.php?t='+thisRow.dataset.target+'&id='+thisRow.dataset.id);
						var nextRow = table.insertRow(nextRowIndex);
						nextRow.classList.add('addedRow');
						colspanValue = thisRow.firstElementChild.getAttribute('colspan');
						if(!colspanValue) colspanValue = thisRow.cells.length;
						nextRow.innerHTML = '<td colspan="'+colspanValue+'">'+responseText+'</td>';
						nextRow.scrollIntoView({block: "center", behavior: "smooth"});
					}		
				});
			}
		}
	});


	function openTab(evt, tabName) {
		var i, tabcontent, tablinks;
		tabcontent = document.getElementsByClassName("tabcontent");
		for (i = 0; i < tabcontent.length; i++) {
			tabcontent[i].style.display = "none";
		}
		tablinks = document.getElementsByClassName("tablinks");
		for (i = 0; i < tablinks.length; i++) {
			tablinks[i].className = tablinks[i].className.replace(" active", "");
		}
		document.getElementById(tabName).style.display = "block";
		evt.currentTarget.className += " active";
	}


	
	async function sendWorks(eventId){
		const formData = new FormData();
		formData.append('eventId', eventId);
		formData.append('cmdAddWorksIntoEvent', 'true');
		var workCount = 0;
		for(const checkedWork of document.querySelectorAll('#workIdArray_'+eventId)){
			if(checkedWork.checked == true){
				workCount++;
				formData.append('workIds[]', checkedWork.value);
			}
			document.getElementById("workCount_"+eventId).innerHTML = '';
			if(workCount > 0){
				document.getElementById("workCount_"+eventId).innerHTML = ' ('+workCount+')';
			}
		}
		await loadUrl('index.php', {'method': 'post', 'body': formData});
	}
	
	async function loadUrl(url, options = [], type = 'text'){
		let response = await fetch(url, options);
		if (response.ok){
		  return await response[type]();
		} else {
		  alert("Ошибка HTTP: " + response.status);
		}				
	}