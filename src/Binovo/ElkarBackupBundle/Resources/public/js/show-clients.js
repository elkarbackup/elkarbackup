/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

// Ask confirmation before delete clients/jobs
var paranoidmode = true;

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
    clone.find('td.status').html('');
    if (client.isActive){
      clone.find('td.isactive').html('Active');
    } else {
      clone.find('td.isactive').html('Inactive');
      clone.addClass('disabled');
    }

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
    clone.find('td.status').html('');
    if (job.isActive){
      clone.find('td.isactive').html('Active');
    } else {
      clone.find('td.isactive').html('Inactive');
      clone.addClass('disabled');
    }

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

function changeClientStatus(clientid, status){
  id = clientid;
  $('tr#client-'+id).addClass(status);
  $('tr#client-'+id).find('td.status').html('<span class="label label-success">' + status + '</span>');
}

function changeJobStatus(jobid, status){
  id = jobid;
  $('tr#job-'+id).addClass(status);
  $('tr#job-'+id).find('td.status').html('<span class="label label-success">' + status + '</span>');
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

/*
 * When abortJob returns True from controller
 * callbackJobAborted will be executed
 */
function callbackJobAborting(jobid){
  changeJobStatus(jobid, 'ABORTING');
}

function deleteJob(path, id, msg, confirmed){
  if (paranoidmode && !confirmed){
    // Modal preparation
    question = $("#deleteModal").find("span.modal-message");
    button = $("#deleteModal").find(":button[eb-action]");
    question.html(msg);
    button.attr('eb-action', 'deleteJob');
    button.attr('eb-path', path);
    button.attr('eb-jobid', id);
    button.attr('eb-action-confirmed', 'true');
    // Show modal
    $("#deleteModal").modal('show');
  } else {
    if (path && id){
      // Hide modal
      $("#deleteModal").modal('hide');
      // Delete job
      r = postRequest(path);
      // If r is ok
      // Delete job row
      jobtr = $('tr.job-'+id).remove();
      // Show feedback message
      okMsg('Job deleted succesfully');
    } else {
      return false;
    }
  }
};

function deleteClient(path, id, msg, confirmed){
  if (paranoidmode && !confirmed){
    // Modal preparation
    question = $("#deleteModal").find("span.modal-message");
    button = $("#deleteModal").find(":button[eb-action]");
    question.html(msg);
    button.attr('eb-action', 'deleteClient');
    button.attr('eb-path', path);
    button.attr('eb-clientid', id);
    button.attr('eb-action-confirmed', 'true');
    // Show modal
    $("#deleteModal").modal('show');
  } else {
    if (path && id){
      // Hide modal
      $("#deleteModal").modal('hide');
      // Delete client
      r = postRequest(path);
      // If r is ok
      // Delete all rows related to the client
      $('tr.client-'+id).remove();
      // Show feedback message
      okMsg('Client deleted successfully');
    } else {
      return false;
    }
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

function deleteSelected(confirmed){
  if (paranoidmode && !confirmed){
    // Modal preparation
    question = $("#deleteModal").find("span.modal-message");
    button = $("#deleteModal").find(":button[eb-action]");
    question.html('Do you really want to delete all the selected clients and jobs?');
    button.attr('eb-action', 'deleteSelected');
    button.attr('eb-action-confirmed', 'true');
    // Show modal
    $("#deleteModal").modal('show');
  } else {
    $("#deleteModal").modal('hide');
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
        deleteClient(path, clientId, null, confirmed=true);
      } else {
        // Job-row
        path = tr.find('a[eb-action="deleteJob"]').attr('eb-path');
        jobId = tr.find('a[eb-action="deleteJob"]').attr('eb-jobid');
        clientid = tr.find('a[eb-action="deleteJob"]').attr('eb-clientid');
        parentrow = $('#client-'+clientid);
        if (parentrow.length > 0){
          // His father is alive
          deleteJob(path, jobId, null, confirmed=true);
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
  }
};

function cloneClient(path, clientId){
  postRequest(path);
};

function runJob(path, id){
  if (path && id){
    r = postRequest(path);
    $('tr#job-'+id).find('td.status').html('<span class="label label-info">QUEUED</span>');
    return true;
  } else {
    return false;
  }
};

function abortJob(path, id, msg, confirmed){
  if (!confirmed){
    // Modal preparation
    question = $("#abortModal").find("span.modal-message");
    button = $("#abortModal").find(":button[eb-action]");
    question.html(msg);
    button.attr('eb-action', 'abortJob');
    button.attr('eb-path', path);
    button.attr('eb-jobid', id);
    button.attr('eb-action-confirmed', 'true');
    // Show modal
    $("#abortModal").modal('show');
  } else {
    if (path && id){
      // Hide modal
      $("#abortModal").modal('hide');
      // Abort job
      r = postRequest(path);
      // Callback will be executed
      // if abortJob is done
    }
  }
}

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
  // Change logentry date to friendly date
  $('td.logentry').each(function(){
    logdate = $(this).find('a');
    if (logdate.html().length > 2){
      flogdate = moment(logdate.html(), "YYYY-MM-DD hh:mm:ss").fromNow();
      logdate.html(flogdate);
    }
  })
  ready(function(){
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
      $("#jobs-container, #deleteModal, #abortModal").on("click", ":button[eb-action], a[eb-action]", function(e){
        var action = $(this).attr("eb-action");
        var path = $(this).attr("eb-path");
        var clientid = $(this).attr("eb-clientid");
        var jobid = $(this).attr("eb-jobid");
        var message = $(this).attr("eb-message");
        var confirmed = $(this).attr("eb-action-confirmed");
        var disabled = $(this).parent().hasClass('disabled');

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
            // Dangerous: ask confirmation
            r = deleteClient(path, clientid, message, confirmed);
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
            // Dangerous: ask confirmation
            r = deleteJob(path, jobid, message, confirmed);
            break;
          case 'runJob':
            if (!disabled){
              if (runJob(path, jobid)){
                // msg should be received from controller (translated)
                okMsg('Job queued successfully. It will start running in less than a minute!');
              } else {
                errorMsg('Error running job');
              }
            }
            break;
          case 'abortJob':
            if (!disabled) {
              r = abortJob(path, jobid, message, confirmed);
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
            r = deleteSelected(confirmed);
            break;
          default:
            console.log('Action not enabled');
      };
    });


});
