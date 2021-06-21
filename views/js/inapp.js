function getInappData() {
  $.post({
      type: "POST",
      url: "/module/cronberryIntegration/inapp?ajax=1&token="+tokencr,
      dataType: "json",
      headers: {
           'Accept': 'application/json',
           'Content-Type': 'application/json'
       },
      success: function (data) {
       $("#inappbody").html(data.quickview_html);
       $('#exampleModal').modal({
          keyboard: false
        })
      },
      error: function () {
        console.log("error occured while sending token to cronberry.");
      }
  });
}

$( document ).ready(function() {
  $('#inappbutton').click(function(){
      if($("#inappbody").html() == ""){
      getInappData();
      }else{
          $('#exampleModal').modal({
              keyboard: false
            })
      }
  });
 

});