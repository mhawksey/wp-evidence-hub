jQuery(document).ready(function($) {

	// after clicking close button
	$(document).on('click', '#cn-accept-cookie', function(event) {
		event.preventDefault();

		var cnTime = new Date();
		var cnLater = new Date();

		// sets new time in seconds
		cnLater.setTime(parseInt(cnTime.getTime()) + parseInt(cnArgs.cookieTime) * 1000);

		// sets cookie
		document.cookie = cnArgs.cookieName+'=true'+';expires='+cnLater.toGMTString()+';'+(cnArgs.cookieDomain !== undefined && cnArgs.cookieDomain !== '' ? 'domain='+cnArgs.cookieDomain+';' : '')+(cnArgs.cookiePath !== undefined && cnArgs.cookiePath !== '' ? 'path='+cnArgs.cookiePath+';' : '');

		// hides box
		if(cnArgs.hideEffect === 'fade') {
			$('#cookie-notice').fadeOut(300, function() {
				$(this).remove();
			});
		} else if(cnArgs.hideEffect === 'slide') {
			$('#cookie-notice').slideUp(300, function() {
				$(this).remove();
			});
		} else {
			$('#cookie-notice').remove();
		}
	});


	// displays cookie notice at start
	if(document.cookie.indexOf('cookie_notice_accepted') === -1) {
		if(cnArgs.hideEffect === 'fade') {
			$('#cookie-notice').fadeIn(300);
		} else if(cnArgs.hideEffect === 'slide') {
			$('#cookie-notice').slideDown(300);
		} else {
			$('#cookie-notice').show();
		}
	} else {
		$('#cookie-notice').remove();
	}
});