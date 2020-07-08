
		function closeMenu(close_list){
			for (var i=0;i < close_list.length;i++){
				document.getElementById(close_list[i]).blur();
				document.getElementById(close_list[i] +'_child').blur();
				//alert ("Closing item " + close_list[i]);
			}
				return false;
		}

