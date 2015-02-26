/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */
 require(['dojo', 'dojo/fx/Toggler', 'dojo/ready'],
 function(dojo, Toggler, ready){
    ready(function() {
              dojo.query('.delete-job,.delete-client')
                  .on('submit',
                           function (e){
                               var msg;
                               msg = dojo.getAttr(e.target, 'data-bnv-message');
                               if (confirm(msg)) {
                                   return true;
                               } else {
                                   e.preventDefault();
                                   return false;
                               }
                           });

                           dojo.query('.runNow')
                            .on('click',
                           function (e){
                             var msg;
                             msg = dojo.getAttr(e.target, 'data-bnv-message');
                             if (confirm(msg)) {
                               var args = {
                                 form: e.target.form,
                                 handleAs: 'text',
                                 load: function(data) {
                                   dojo.place('<div class="controls help-block alert alert-success">' + data + '</div>', dojo.byId('legend'), 'after');
                                 },
                                 error: function(data) {
                                   dojo.place('<div class="controls help-block alert alert-danger">' + data + '</div>', dojo.byId('legend'), 'after');
                                 }
                               };
                               console.log(args);
                               dojo.xhrPost(args);
                             } else {
                                 e.preventDefault();
                                 return false;
                             }
                          });
          });
});
