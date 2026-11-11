/// redirect to 403 page if not logged in
if (document.referrer === "" || !document.referrer.includes(window.location.hostname)) {
    window.location.href = "../error/403.html";
} else {
    window.location.href = document.referrer;
}

/// check if user is logged in
function checkUserLoggedIn() {
    let userToken = localStorage.getItem('userToken');
    if (userToken) {
        console.log('User is logged in');
        return true;
    } else {
        console.log('User is not logged in');
        return false;
    }
}

// check if user is Sales Manager
function checkUserRole() {
    let userRole = localStorage.getItem('role');
    if (userRole === 'Sales Manager') {
        console.log('User is Sales Manager');
        return true;
    } else {
        console.log('User is not Sales Manager');
        return false;
    }
}


//check if user is logged in and Sales Manager
function checkUserLoggedInAndRole() {
    let userToken = localStorage.getItem('userToken');
    let userRole = localStorage.getItem('role');
    if (userToken && userRole === 'Sales Manager') {
        console.log('User is logged in and Sales Manager');
        return true;
    } else {
        console.log('User is not logged in or not Sales Manager');
        return false;
    }
}