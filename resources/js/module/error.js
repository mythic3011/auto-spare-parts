// error.js

// redirect to the previous page after 5 seconds
export function Redirect() {
    const countdownElement = document.getElementById("countdown-text");
    let timeLeft = parseInt(countdownElement.innerHTML);
    if (isNaN(timeLeft)) {
        console.error('Invalid countdown value');
        return;
    }
    const countdownTimer = setInterval(function () {
        const seconds = timeLeft % 60;
        countdownElement.innerHTML = seconds + "s";
        if (timeLeft === 0) {
            clearInterval(countdownTimer);
            window.location.href = "../index.php";
        }
        timeLeft--;
    }, 1000);
}

// check if the user is not coming from the same domain or direct access this page
if (document.referrer === "" || !document.referrer.includes(window.location.hostname)) {
    window.location.href = "../index.php";
} else {
    Redirect();
}

// redirect to 404 page
export function redirect404() {
    window.location.href = '../error/404.html';
}

// redirect to 403 page
export function redirect403() {
    window.location.href = '../error/403.html';
}

// redirect to 500 page
export function redirect500() {
    window.location.href = '../error/500.html';
}

// redirect to 503 page
export function redirect503() {
    window.location.href = '../error/503.html';
}