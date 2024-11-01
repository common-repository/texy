miu = {
	texyTitle: function (markItUp, char) {
		heading = '';
		n = jQuery.trim(markItUp.selection || markItUp.placeHolder).length;
		for(i = 0; i < n; i++) heading += char;
		return '\n' + heading;
	}
}

function popUp(URL) {
day = new Date();
id = day.getTime();
eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=850,height=550,left = 100,top = 100');");
}

// start Markitup
jQuery(document).ready(function() {
	jQuery('#content').markItUp(mySettings);
});

function edToolbar () {}