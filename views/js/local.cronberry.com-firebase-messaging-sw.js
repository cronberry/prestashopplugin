importScripts("https://www.gstatic.com/firebasejs/8.6.2/firebase-app.js");
        importScripts("https://www.gstatic.com/firebasejs/8.6.2/firebase-messaging.js");
        var firebaseConfig = {"apiKey":false,"authDomain":false,"databaseURL":false,"projectId":false,"storageBucket":false,"messagingSenderId":false,"appId":false,"measurementId":false}
        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();
