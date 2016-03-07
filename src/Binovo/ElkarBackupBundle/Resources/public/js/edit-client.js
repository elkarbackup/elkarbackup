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
function sendForm(){
  var form = dojo.byId("myform");

  dojo.connect(form, "onsubmit", function(event){
    // Stop the submit event since we want to control form submission.
  dojo.stopEvent(event);

    // url ez diogu sartu beraz formularioaren action bera hartuko du url gixa.
  var xhrArgs = {
      form: dojo.byId("myform"),
      handleAs: "text",
      load: function(response){
                    if (response == 'success'){
                            dijit.byId("dialog1").hide();
                            dojo.place('<div class="controls help-block alert alert-success">' + response + '</div>', dojo.byId('legend'), 'after');
                            dojo.byId("pwd").value = '';
                            dojo.byId("response").innerHTML = '';
                    } else {
                            dojo.byId("response").innerHTML = '<div class="alert-danger alert help-block controls">' + response + '</div>';
                    }

                  },
      error: function(){
                    dojo.byId("response").innerHTML = '<div class="alert-danger alert help-block controls">' + response.data + '</div>';
                  }
                }
    // Call the asynchronous xhrPost
    dojo.byId("response").innerHTML = "Form being sent..."
    var deferred = dojo.xhrPost(xhrArgs);
        });
      }
    dojo.ready(sendForm);

    });
});
