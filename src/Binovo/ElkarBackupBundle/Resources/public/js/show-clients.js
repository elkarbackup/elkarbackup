/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
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
  $("#legend").after('<div class="controls help-block alert alert-danger fade in"><a title="close" aria-label="close" data-dismiss="alert" class="close" href="#">×</a>' + msg + '</div>');
}

function addClientRow(client){
  var c = client;
  if (c.id){
    var table = $('table');
    var parent = $('.client-row').first();
    var clone = parent.clone();
    clone.prop('id', 'client-'+c.id);
    clone.removeClass();
    clone.addClass('client-row client-'+c.id);
    clone.find('td.id').html('<a href="/client/'+c.id+'">'+c.id+'</a>');
    clone.find('td.name').html('<a href="/client/'+c.id+'">'+c.name+'</a>');
    clone.find('td.diskusage').html('0 MB');
    clone.find('td.logentry').html('');
    clone.find('td.isactive').html('Active');

    // Action buttons, custom attributes
    //    editClient button
    clone.find(':button[eb-action="editClient"]').attr('eb-path', '/client/'+c.id);
    clone.find(':button[eb-action="editClient"]').attr('eb-clientid', c.id);
    //    addJob button
    clone.find(':button[eb-action="addJob"]').attr('eb-path', '/client/'+c.id+'/job/new');
    clone.find(':button[eb-action="addJob"]').attr('eb-clientid', c.id);
    //    cloneClient a
    clone.find('a[eb-action="runClient"]').attr('eb-clientid', c.id);
    //    cloneClient a
    clone.find('a[eb-action="cloneClient"]').attr('eb-path', '/client/clone/'+c.id);
    clone.find('a[eb-action="cloneClient"]').attr('eb-clientid', c.id);
    //    deleteClient a
    clone.find('a[eb-action="deleteClient"]').attr('eb-path', '/client/'+c.id+'/delete');
    clone.find('a[eb-action="deleteClient"]').attr('eb-clientid', c.id);

    // Add row
    table.append(clone);
    return true;
  } else {
    return false;
  }
}

function addJobRow(job, client){
  var j = job;
  var c = client;
  console.log(c);
  if (j.id){
    var table = $('table');
    var parent = $('.job-row').first();
    var clone = parent.clone();
    clone.prop('id', 'job-'+j.id);
    clone.removeClass();
    clone.addClass('job-row client-'+c.id+' job-'+j.id);
    clone.find('td.id').html('<a href="/client/'+c.id+'/job/'+j.id+'">'+c.id+'.'+j.id+'</a>');
    clone.find('td.name').html('<a href="/client/'+j.id+'">'+c.name+'/'+j.name+'</a>');
    clone.find('td.diskusage').html('0 MB');
    clone.find('td.logentry').html('');
    clone.find('td.isactive').html('Active');

    // Action buttons, custom attributes
    //    editJob button
    clone.find(':button[eb-action="editJob"]').attr('eb-path', '/client/'+c.id+'/job/'+j.id);
    clone.find(':button[eb-action="editJob"]').attr('eb-jobid', j.id);
    //    showJobBackup button
    clone.find(':button[eb-action="showJobBackup"]').attr('eb-path', '/client/'+c.id+'/job/'+j.id+'/backup/view');
    clone.find(':button[eb-action="showJobBackup"]').attr('eb-jobid', j.id);
    clone.find(':button[eb-action="showJobBackup"]').addClass('disabled');
    //    runJob a
    clone.find('a[eb-action="runJob"]').attr('eb-path', '/client/'+c.id+'/job/'+j.id+'/run');
    clone.find('a[eb-action="runJob"]').attr('eb-jobid', j.id);
    //    abortJob a
    clone.find('a[eb-action="abortJob"]').attr('eb-path', '');
    clone.find('a[eb-action="abortJob"]').attr('eb-jobid', j.id);
    //    deleteJob a
    clone.find('a[eb-action="deleteJob"]').attr('eb-path', '/client/'+c.id+'/job/'+j.id+'/delete');
    clone.find('a[eb-action="deleteJob"]').attr('eb-jobid', j.id);

    // Add row
    table.append(clone);
    return true;
  } else {
    return false;
  }
}


/*
 * When cloneClient returns True from controller
 * callbackClonedClient will be executed
 */
function callbackClonedClient(data){
  var c = $.parseJSON(data);
  if (addClientRow(c)){
    $.each(c.jobs, function(i, job){
      addJobRow(job, c);
    });
  } else {
    console.log('Error adding client row');
  }
}

function deleteJob(path, id){
  if (path && id){
    r = postRequest(path);
    // If r is ok
    // Delete job row
    jobtr = $('tr.job-'+id).remove();
    // Show feedback message
    okMsg('Job deleted succesfully');
  } else {
    return false;
  }
};

function deleteClient(path, id){
  if (path && id){
    r = postRequest(path);
    // If r is ok
    // Delete all rows related to the client
    $('tr.client-'+id).remove();
    // Show feedback message
    okMsg('Client deleted successfully');
  } else {
    return false;
  }
};

function runClient(clientid){
  if (clientid){
    $('tr.client-'+clientid).each(function(){
      // Jobs
      path = $(this).find('a[eb-action="runJob"]').attr('eb-path');
      jobid = $(this).find('a[eb-action="runJob"]').attr('eb-jobid');
      if (path && jobid){
        runJob(path, jobid);
      }
      $(this).addClass('queued');
    });
    return true;
  } else {
    return false;
  }
};

function runSelected(){
  var error = false;
  if($('input:checkbox:checked').not('#checkAll').length == 0){
    error = "Nothing to run. Did you select any job/client?";
  }
  $('input:checkbox:checked').not('#checkall').each(function(){
    tr = $(this).parents(':eq(1)');
    if (tr.hasClass('client-row')){
      // Client-row
      clientid = tr.find('a[eb-action="runClient"]').attr('eb-clientid');
      runClient(clientid);
      // This will add the class "queued" to the rows
    } else {
      // Job-row
      path = tr.find('a[eb-action="runJob"]').attr('eb-path');
      jobid = tr.find('a[eb-action="runJob"]').attr('eb-jobid');
      if (tr.hasClass('queued')){
        // Job is already queued
        console.log('Job already queued');
      } else {
        // Job available
        r = runJob(path, jobid);
      }
    }
  });
  if (error){
    errorMsg('Error queueing jobs: '+error);
  } else {
    okMsg('Job(s) queued successfully');
  }
};

function deleteSelected(){
  var error = false;
  if($('input:checkbox:checked').not('#checkAll').length == 0){
    error = "Nothing to delete. Did you select any item?";
  }
  $('input:checkbox:checked').not('#checkall').each(function(){
    tr = $(this).parents(':eq(1)');
    if (tr.hasClass('client-row')){
      // Client-row
      path = tr.find('a[eb-action="deleteClient"]').attr('eb-path');
      clientId = tr.find('a[eb-action="deleteClient"]').attr('eb-clientid');
      deleteClient(path, clientId);
    } else {
      // Job-row
      path = tr.find('a[eb-action="deleteJob"]').attr('eb-path');
      jobId = tr.find('a[eb-action="deleteJob"]').attr('eb-jobid');
      clientid = tr.find('a[eb-action="deleteJob"]').attr('eb-clientid');
      parentrow = $('#client-'+clientid);
      if (parentrow.length > 0){
        // His father is alive
        deleteJob(path, jobId);
      } else {
        console.log('Job was already deleted');
      }
    }
  });
  if (error){
    errorMsg('Error deleting: '+error);
  } else {
    okMsg('Items deleted successfully');
  }
};


function cloneClient(path, clientId){
  postRequest(path);
};

function runJob(path, id){
  if (path && id){
    r = postRequest(path);
    $('tr#job-'+id).addClass('queued');
    return true;
  } else {
    return false;
  }
};

function postRequest(url, params) {
  $.ajax({
    type: "POST",
    url: url,
    data: params,
    success: function(response) {
      if (response.msg){
        okMsg(response.msg);

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


function showJobBackup(path, id) {
  if (path){
    window.location.href = path;
    return true;
  }
}

function editJob(path, id) {
  if (path){
    window.location.href = path;
    return true;
  }
};

function addJob(path, id) {
  if (path){
    window.location.href = path;
    return true;
  }
}

function editClient(path, id) {
  if (path){
    window.location.href = path;
    return true;
  }
};

function addClient(path){
  if (path){
    window.location.href = path;
    return true;
  }
}

function sortJobs(path){
  if (path){
    window.location.href = path;
    return true;
  }
}


//
// I need to put this section to use moment.js
//
require(['dojo/ready','js/moment/moment.min.js'],
function(ready, moment){
  ready(function(){
    // Change logentry date to friendly date
    $('td.logentry').each(function(){
      logdate = $(this).find('a');
      flogdate = moment(logdate.html(), "YYYY-MM-DD hh:mm:ss").fromNow();
      logdate.html(flogdate);
    })
  });
});


$(document).ready(function(){
      // Checkbox select/deselect all option
      $('#checkAll').click(function(){
        $('input:checkbox').not(this).prop('checked', this.checked);
      });

      //
      // Listeners, they work even for the dynamically created buttons
      //
      $("#jobs-container").on("click", ":button[eb-action], a[eb-action]", function(){
        var action = $(this).attr("eb-action");
        var path = $(this).attr("eb-path");
        var clientid = $(this).attr("eb-clientid");
        var jobid = $(this).attr("eb-jobid");

        switch(action){
          case 'sortJobs':
            r = sortJobs(path);
            break;
          case 'addClient':
            r = addClient(path);
            break;
          case 'editClient':
            r = editClient(path,clientid);
            // Will be redirected
            break;
          case 'deleteClient':
            r = deleteClient(path, clientid);
            break;
          case 'cloneClient':
            r = cloneClient(path, clientid);
            break;
          case 'runClient':
            r = runClient(clientid);
            break;
          case 'addJob':
            r = addJob(path,clientid);
            // Will be redirected
            break;
          case 'editJob':
            r = editJob(path,jobid);
            // Will be redirected
            break;
          case 'deleteJob':
            r = deleteJob(path, jobid);
            break;
          case 'runJob':
            if (runJob(path, jobid)){
              // msg should be received from controller (translated)
              okMsg('Job queued successfully. It will start running in less than a minute!');
            } else {
              errorMsg('Error running job');
            }
            break;
          case 'showJobBackup':
            r = showJobBackup(path,jobid);
            // Will be redirected
            break;
          case 'runSelected':
            r = runSelected();
            break;
          case 'deleteSelected':
            r = deleteSelected();
            break;
          default:
            console.log('Action not enabled');
      };
    });


});
