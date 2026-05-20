let noon = 12;
let showCurrentTime = function () {
    let clock = document.getElementById('clock');
    let currentTime = new Date();
    let hours = currentTime.getHours();
    let minutes = currentTime.getMinutes();
    let seconds = currentTime.getSeconds();
    let meridian = "AM";

    if (hours >= noon) {
        meridian = "PM";
    }
    if (hours > noon) {
        hours = hours - 12;
    }
    if (minutes < 10 ) {
        minutes = "0" + minutes;
    }
    if (seconds < 10) {
        seconds = "0" + seconds;
    }

    let clockTime = hours + ':' + minutes + ':' + seconds + "" + meridian + "!";
    clock.innerText = clockTime
};

setInterval(showCurrentTime, 1000);

// Affiche l'heure immédiatement au chargement
showCurrentTime();

