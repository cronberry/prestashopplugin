
 

 firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();
  
  
  messaging.onMessage((payload) => {
    console.log('Message received. ', payload);
    appendMessage(payload);
  });
 
  messaging.getToken().then((currentToken) => {
      if (currentToken) {
        if(window.sessionStorage.getItem('sentToServer') != currentToken){
            sendTokenToServer(currentToken);
        }   
      } else {
        console.log('No Instance ID token available. Request permission to generate one.');
      }
    }).catch((err) => {
      console.log('An error occurred while retrieving token. ', err);
    });

   function sendTokenToServer(currentToken) {
      setTokenSentToServer(currentToken);
  }

  
  function setTokenSentToServer(token) {
    $.post({
        type: "POST",
        url: "/module/cronberryIntegration/ajax?ajax=1&token="+tokencr+"&fcm="+token,
        dataType: "json",
        headers: {
             'Accept': 'application/json',
             'Content-Type': 'application/json'
         },
        success: function (data) {
          console.log("token " + token + " sent to cronberry.");
         window.sessionStorage.setItem('sentToServer', token) ;
        },
        error: function () {
          console.log("error occured while sending token to cronberry.");
        }
    });
  }
 // if(window.localStorage.getItem('sentToServer') !== '1'){
  

  function requestPermission() {
    console.log('Requesting permission...');
    // [START request_permission]
    if(window.sessionStorage.getItem('sentToServer') == undefined  ){
        sendTokenToServer("");
    }
    Notification.requestPermission().then((permission) => {
      if (permission === 'granted') {
        console.log('Notification permission granted.');
      } else {
       
        console.log('Unable to get permission to notify.');
      }
    });
    // [END request_permission]
  }

 
  $( document ).ready(function() {
    $.ajaxSetup({
      beforeSend: function (xhr,settings) {
          if(settings.url.indexOf("/cart") != -1){
            settings.data += "&fcm="+window.sessionStorage.getItem('sentToServer');
          }
      }
  });

});

  

  
