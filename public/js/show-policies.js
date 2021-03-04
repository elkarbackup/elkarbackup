/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */
require(['dojo', 'dojo/ready'],
function(dojo, ready){
    ready(function() {
              dojo.query('.delete-policy')
                  .connect('onsubmit',
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
          });
});
