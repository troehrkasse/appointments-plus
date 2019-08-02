if ((window.location.href.includes('massage-appointments') || window.location.href.includes('coaching-appointments')) && !window.location.href.includes('?email=')) {
    document.location.search += "?email=" + currentUser.email + "&mobile=" + currentUser.phone;
}
