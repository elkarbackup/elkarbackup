/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */
require(['dojo', 'dojo/ready', 'dijit/TitlePane', 'dojo/parser', 'dijit/Dialog' ],
function(dojo, ready, TitlePane, dom){
    ready(function() {
              dojo.query('.delete-job')
                  .connect('onclick',
                           function (e){
                               var msg;
                               msg = dojo.getAttr(e.target, 'data-bnv-message');
                               if (confirm(msg)) {
                                   dojo.query(dojo.getAttr(e.target, 'data-bnv-job-id')).remove();
                               }
                           });

    });
});
