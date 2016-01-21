require(['dojo', 'dojo/ready', 'dojox/form/CheckedMultiSelect', 'dijit/TitlePane', 'dojo/parser' ],
function(dojo, ready, TitlePane, dom){
     ready(function() {
//
//       $('#showSshModal').click(function(e){
//         console.log('pulsado boton');
//       });
//
//
//
//               dojo.query('.delete-job')
//                   .connect('onclick',
//                            function (e){
//                                var msg;
//                                msg = dojo.getAttr(e.target, 'data-bnv-message');
//                                if (confirm(msg)) {
//                                    dojo.query(dojo.getAttr(e.target, 'data-bnv-job-id')).remove();
//                                }
//                            });

                          //  function sendForm(){
                          //    var form = dojo.byId("myform");
                           //
                          //    dojo.connect(form2, "onsubmit", function(event){
                          //      // Stop the submit event since we want to control form submission.
                          //    dojo.stopEvent(event);
                           //
                          //      // url ez diogu sartu beraz formularioaren action bera hartuko du url gixa.
                          //    var xhrArgs = {
                          //        form: dojo.byId("myform2"),
                          //        handleAs: "text",
                          //        load: function(response){
                          //                      if (response == 'success'){
                          //                              dijit.byId("dialog1").hide();
                          //                              dojo.place('<div class="controls help-block alert alert-success">' + response + '</div>', dojo.byId('legend'), 'after');
                          //                              dojo.byId("pwd").value = '';
                          //                              dojo.byId("response").innerHTML = '';
                          //                      } else {
                          //                              dojo.byId("response").innerHTML = '<div class="alert-danger alert help-block controls">' + response + '</div>';
                          //                      }
                           //
                          //                    },
                          //        error: function(){
                          //                      dojo.byId("response").innerHTML = '<div class="alert-danger alert help-block controls">' + response.data + '</div>';
                          //                    }
                          //                  }
                          //      // Call the asynchronous xhrPost
                          //      dojo.byId("response").innerHTML = "Form being sent..."
                          //      var deferred = dojo.xhrPost(xhrArgs);
                          //          });
                          //        }
                          //      dojo.ready(sendForm);
                           //
                          //      });
                          //  });

                  //
                  //
                      $('.collapsesudo').collapse(); //ocultamos el apartado de sudo

                      $('form#sshform').on('submit', function(e){
                        e.preventDefault();
                        var data = $("form#sshform").serialize();
                        $.ajax({
                                    type        : $('form#sshform').attr( 'method' ),
                                    url         : $('form#sshform').attr( 'action' ),
                                    data        : $('form#sshform').serialize(),
                        success     : function(data, status, object) {
                          if (data == 'success'){
                                                  alert('guay');
                                                        //dijit.byId("dialog1").hide();
                                                        //dojo.place('<div class="controls help-block alert alert-success">' + response + '</div>', dojo.byId('legend'), 'after');
                                                        //dojo.byId("pwd").value = '';
                                                        //dojo.byId("response").innerHTML = '';
                                                } else {
                                                  alert('error');
                                                        //dojo.byId("response").innerHTML = '<div class="alert-danger alert help-block controls">' + response + '</div>';
                                                }
                        },
                        error: function ()
                        {
                        alert('Error ajax');
   // document.getElementById('loaderInt1').style.display = 'none';
                        }
                });
              });




                      });
                   });
