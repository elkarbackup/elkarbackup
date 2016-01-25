require(['dojo', 'dojo/ready', 'dijit/TitlePane', 'dojo/parser' ],
function(dojo, ready, TitlePane, dom){
     ready(function() {


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

                                  $("#legend").after('<div class="controls help-block alert alert-success">' + data + '</div>');
                                  $('#SSHCopyModal').modal('toggle');
                                  $('#pwd').val('');
                                  $(".alert-success").delay(5000).fadeOut();
                                  $("#showSshModal").prop( "disabled", true );
                          } else {
                                  $("#exampleModalLabel").after('<div class="alert-danger alert help-block controls">' + data + '</div>');
                                  $(".alert-danger").delay(5000).fadeOut();
                          }
                        },
                        error: function ()
                        {
                        alert('Error ajax');
                        }
                      }); // end of ajax
              }); // end of submit function
        }); // end of ready
 }); // end general function
