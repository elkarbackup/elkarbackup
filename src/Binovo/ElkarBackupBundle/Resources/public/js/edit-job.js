/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */
require(['dojo', 'dojo/fx/Toggler', 'dojo/ready'],
function(dojo, Toggler, ready){
    var initNotificationsEmail;

    initNotificationsEmail = function() {
        var toggler, checkbox, showHideEmailBox;
        showHideEmailBox = function() {
            if (checkbox.checked) {
                toggler.show();
            } else {
                toggler.hide();
            }            
        };
        toggler = new Toggler({node:'Job_notificationsEmail'});
        checkbox = dojo.byId('Job_notificationsTo_2');
        dojo.connect(checkbox, 'onchange', showHideEmailBox);
        showHideEmailBox();
    };
    ready(function() {
              initNotificationsEmail();
              dojo.connect(dojo.byId('runNow'), 
	 	                   'onclick', 
	 	                   function() { 
	 	                       var args = { 
	 	                           form: dojo.byId('runNowForm'), 
	 	                           handleAs: 'text', 
	 	                           load: function(data) { 
	 	                               dojo.place('<div class="controls help-block">' + data + '</div>', dojo.byId('legend'), 'after'); 
	 	                               dojo.attr(dojo.byId('runNow'), 'disabled', true) ;
	 	                           }, 
	 	                           error: function(data) { 
	 	                               dojo.place('<div class="controls help-block">' + data + '</div>', dojo.byId('legend'), 'after'); 
	 	                           } 
	 	                       }; 
	 	                       console.log(args); 
	 	                       dojo.xhrPost(args); 
	 	                   }); 
          });

});
