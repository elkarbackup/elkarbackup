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
          });
});
