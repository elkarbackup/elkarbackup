/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */



/*
 * Feedback messages
 */
function okMsg(msg){
   // Remove previous messages
   $("div.alert").remove();
   // Print new message
   $("#legend").after('<div class="controls help-block alert alert-success fade in" role="alert">' +
       '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
       '<span aria-hidden="true">&times;</span></button>' + msg + '</div>');
}

function errorMsg(msg){
   // Remove previous messages
   $("div.alert").remove();
   // Print new message
   $("#legend").after('<div class="controls help-block alert alert-danger fade in" role="alert">' +
       '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
       '<span aria-hidden="true">&times;</span></button>' + msg + '</div>');
}

/*
 * POST requests
 */
function postRequest(url, params) {
   $.ajax({
     type: "POST",
     url: url,
     data: params,
     success: function(response) {
       if (response.msg){
         if (response.error){
           errorMsg(response.msg);
         } else {
           okMsg(response.msg);
         }

         if (response.action){
           if (response.data){
             // Call to callback
             window[response.action].apply(null, response.data);
           }
         }
       }
     }
   });
 };




require(['dojo', 'dojo/parser', 'dijit/form/Button', 'dijit/PopupMenuBarItem', 'dijit/DropDownMenu', 'dojo/NodeList-manipulate', 'dojo/ready', 'dojo/dom-class', 'dojo/query!css2', 'dijit/registry'],
function(dojo, parser, Button, PopuMenuBarItem, DropDownMenu, NodeList, ready, domClass, query, registry) {
    ready(function() {
              parser.parse().then(function(){

              //  dojo.destroy('menuplaceholder');
               });
          });
});
