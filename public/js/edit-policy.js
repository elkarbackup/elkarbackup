/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */
require(['dojo', 'dojo/dom-construct', 'dijit/form/TimeTextBox', 'dojo/store/Memory', 'dojo/string', 'dijit/layout/TabContainer', 'dijit/layout/ContentPane', 'dijit/registry', 'dojo/dom-class', 'dojo/window', 'dojo/ready'],
function(dojo, domConstruct, TimeTextBox, Memory, string, TabContainer, ContentPane, registry, domClass, win, ready){
    var dateToHour, englishWeekDayToIdx, getHourlyHours, hourToDate, idxToEnglishWeekDay, initActivationCheckboxes, initDailyDays, initDailyCount, initDailyHour, initHourlyDays, initHourlyHours, initMonthlyCount, initMonthlyDayOfMonth, initMonthlyHour, initSaveButton, initWeeklyCount, initWeeklyDayOfWeek, initWeeklyHour, initYearlyCount, initYearlyDay, initYearlyHour, newTimeWidget, onSubmitClick;
    dateToHour = function (d) {
        if (d) {
          return string.pad(d.getHours(), 2, '0') + ":" + string.pad(d.getMinutes(), 2, '0');
        }
    };
    englishWeekDayToIdx = function(weekday) {
        var dayToIndex = {monday:1, tuesday:2, wednesday:3, thursday:4, friday:5, saturday:6, sunday:7};
        if (dayToIndex[weekday]) {
            return dayToIndex[weekday];
        } else {
            return 0;
        }
    };
    getHourlyHours = function() {
        var hourlyHoursInput;
        hourlyHoursInput = dojo.byId('Policy_hourlyHours');
        return dojo.map(dojo.map(dojo.filter(hourlyHoursInput.value.split('|'), function(item){return item;}), hourToDate).sort(), dateToHour);
    };
    hourToDate = function(hour) {
        var date, h, m;
        date = new Date();
        if (hour) {
            h = Number(hour.split(':')[0]);
            m = Number(hour.split(':')[1]);
            if (!isNaN(h) && h >= 0 && h <= 23 && !isNaN(m) && m >= 0 && m <= 59) {
                date.setHours(h);
                date.setMinutes(m);
            }
        }
        return date;
    };
    idxToEnglishWeekDay = function(dayIdx) {
        var days = ['', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        dayIdx = Number(dayIdx);
        if (dayIdx >= 1 && dayIdx <= 7) {
            return days[dayIdx];
        }
        return '';
    };
    initActivationCheckboxes = function() {
        function setStateWidgets(checkbox) {
            var panel       = checkbox.parentNode.parentNode,
                panelWidget = registry.getEnclosingWidget(panel);
            dojo.query('input', panel).forEach(
                function(element) {
                    var widget = registry.getEnclosingWidget(element);
                    if (widget != panelWidget) {
                        widget.set('disabled', !checkbox.checked);
                    } else {
                        dojo.setAttr(element, 'disabled', !checkbox.checked);
                    }
                });
            dojo.query('button', panel).forEach(
                function(element) {
                  var widget = registry.getEnclosingWidget(element);
                  if (widget != panelWidget) {
                      widget.set('disabled', !checkbox.checked);
                  } else {
                      dojo.setAttr(element, 'disabled', !checkbox.checked);
                  }
                });
            if (!checkbox.checked) {
                registry.byNode(dojo.query('[id*=Count]', panel)[0]).set('value', 0);
            }
            dojo.setAttr(checkbox, 'disabled', false);
        }
        dojo.query('.activation-controller')
            .on('change',
                function(e) {
                    setStateWidgets(this);
                });
        dojo.query('.activation-controller')
            .forEach(function(checkbox) {
                         var panel = checkbox.parentNode.parentNode;
                         dojo.setAttr(checkbox, 'checked', 0 != registry.getEnclosingWidget(dojo.query('[id*=Count]', panel)[0]).get('value'));
                         setStateWidgets(checkbox);
                     });
    };

    initDailyCount = function() {
        var value;
        value = Number(dojo.byId('Policy_dailyCount').value);
        dijit.byId('dailyCount').set('value', !!value  ? value : 0);
    };
    initDailyHour = function() {
        var value, widget, store;
        widget = dijit.byId('dailyHour');
        if (dojo.byId('Policy_dailyHours').value) {
            value = hourToDate(dojo.byId('Policy_dailyHours').value);
        } else {
            value = '';
        }
        widget.set('value', value);
    };
    initDailyDays = function() {
        dojo.query('input[id^=daily][type=checkbox]').forEach(function(input){input.checked = false;});
        dojo.forEach(dojo.byId('Policy_dailyDaysOfWeek').value.split('|'),
                     function(dayIdx) {
                         dayIdx = Number(dayIdx);
                         if (dayIdx >= 1 && dayIdx <= 7) {
                             dojo.byId('daily-' + idxToEnglishWeekDay(dayIdx)).checked = true;
                         }
                     });
    };
    initHourlyDays = function() {
        var dailyHoursInput;
        dailyHoursInput = dojo.byId('Policy_dailyHours');
        dojo.forEach(dojo.byId('Policy_hourlyDaysOfWeek').value.split('|'),
                     function(dayIdx) {
                         dayIdx = Number(dayIdx);
                         if (dayIdx >= 1 && dayIdx <= 7) {
                             dojo.setAttr(dojo.byId('hourly-' + idxToEnglishWeekDay(dayIdx)), 'checked', true);
                         }
                     });
    };
    initHourlyHours = function() {
        var hourlyCountInput, hourlyCountWidget;
        hourlyCountInput = dojo.byId('Policy_hourlyCount');
        dojo.forEach(getHourlyHours(),
                     function(time, i){
                         var timeWidget;
                         timeWidget = newTimeWidget(time);
                     });
        hourlyCountWidget = dijit.byId('hourlyCount');
        dojo.connect(dojo.byId('hourlyHoursAdd'),
                     'onclick',
                     function() {
                         if (dojo.getAttr(dojo.byId('duringTheDay-activation'), 'checked')) {
                             newTimeWidget();
                         }});
        hourlyCountWidget.set('value', Number(hourlyCountInput.value));
    };
    initMonthlyCount = function() {
        var value;
        value = Number(dojo.byId('Policy_monthlyCount').value);
        if (isNaN(value)) {
            value = 0;
        }
        dijit.byId('monthlyCount').set('value', value);
    };
    initMonthlyDayOfMonth = function() {
        var value;
        value = Number(dojo.byId('Policy_monthlyDaysOfMonth').value);
        if (value >= 1 && value <= 31) {
            dijit.byId('dayOfMonth').set('value', value);
        }
    };
    initMonthlyHour = function() {
        var value, widget;
        widget = dijit.byId('monthlyHour');
        if (dojo.byId('Policy_monthlyHours').value) {
            value = hourToDate(dojo.byId('Policy_monthlyHours').value);
        } else {
            value = null;
        }
        widget.set('value', value);
    };
    initSaveButton = function() {
        var button;
        button = dojo.query('[type=submit]')[0];
        dojo.connect(button, 'onclick', onSubmitClick);
    };
    initWeeklyCount = function() {
        var value;
        value = Number(dojo.byId('Policy_weeklyCount').value);
        if (isNaN(value)) {
            value = 0;
        }
        dijit.byId('weeklyCount').set('value', value);
    };
    initWeeklyDayOfWeek = function() {
        var dayIdx;
        dayIdx = Number(dojo.byId('Policy_weeklyDaysOfWeek').value.split('|')[0]);
        if (dayIdx >= 1 && dayIdx <= 7) {
            dojo.byId('weekly-' + idxToEnglishWeekDay(dayIdx)).checked = true;
        }
    };
    initWeeklyHour = function() {
        var value, widget, store;
        widget = dijit.byId('weeklyHour');
        if (widget.get('value')) {
            value = widget.get('value');
        } else if (dojo.byId('Policy_weeklyHours').value) {
            value = hourToDate(dojo.byId('Policy_weeklyHours').value);
        } else {
            value = null;
        }
        widget.set('value', value);
    };
    initYearlyCount = function() {
        var value;
        value = Number(dojo.byId('Policy_yearlyCount').value);
        if (isNaN(value)) {
            value = 0;
        }
        dijit.byId('yearlyCount').set('value', value);
    };
    initYearlyDay = function() {
        var year, month, day;
        year  = new Date().getFullYear();
        month = string.pad(Number(dojo.byId('Policy_yearlyMonths').value.split('|')[0]), 2, '0');
        day   = string.pad(Number(dojo.byId('Policy_yearlyDaysOfMonth').value.split('|')[0]), 2, '0');
        dijit.byId('dayOfYear').set('value', new Date(year + '-' + month + '-' + day));
    };
    initYearlyHour = function() {
        var value, widget, store;
        widget = dijit.byId('yearlyHour');
        if (widget.get('value')) {
            value = widget.get('value');
        } else if (dojo.byId('Policy_yearlyHours').value) {
            value = hourToDate(dojo.byId('Policy_yearlyHours').value);
        } else {
            value = null;
        }
        widget.set('value', value);
    };
    /**
     * Create and place a TimeTexBox with its value set to hour. Hour is a string of time "HH:mm", default "09:00"
     */
    newTimeWidget = function(hour) {
        var timeWidget, p, removeButton;
        timeWidget = TimeTextBox({value: hourToDate(hour),
                                  constraints: {
                                      timePattern: 'HH:mm',
                                      clickableIncrement: 'T00:15:00',
                                      visibleIncrement: 'T00:15:00',
                                      visibleRange: 'T01:00:00'
                                  }});
        p = domConstruct.place("<p></p>", "hourlyHoursAdd", "before");
        domConstruct.place(timeWidget.domNode, p, 'last');
        removeButton = domConstruct.place('<button type="button" class="btn btn-default btn-sm" style="margin-left:0.5em"><span class="glyphicon-minus"></button>', p, 'last');
        dojo.connect(removeButton, "onclick",(function(p){
                                                  return function() {
                                                      if (dojo.getAttr(dojo.byId('duringTheDay-activation'), 'checked')) {
                                                          domConstruct.destroy(p);
                                                      }
                                                  };})(p));
        return timeWidget;
    };
    function isFormValid() {
        var i,
            valid       = true,
            activations = ['duringTheDay-activation', 'daily-activation', 'weekly-activation', 'monthly-activation', 'yearly-activation'],
            counts      = ['hourlyCount'            , 'dailyCount'      , 'weeklyCount'      , 'monthlyCount'      , 'yearlyCount'],
            rotationMsg = 'rotationMsg',
            messages    = ['hourlyCountMsg'         , 'dailyCountMsg'   , 'weeklyCountMsg'   , 'monthlyCountMsg'   , 'yearlyCountMsg', rotationMsg],
            countWidget, activeInput;
        dojo.forEach(messages, function(msgId) {
                         domClass.add(msgId, 'hide');
                     });
        // do not allow activating a block but setting its count to 0
        for (i = 0; i < Math.min(counts.length, activations.length); ++i) {
            countWidget = dijit.byId(counts[i]);
            activeInput = dojo.byId(activations[i]);
            if (0 == countWidget.value && activeInput.checked) {
                valid = false;
                domClass.remove(messages[i], 'hide');
                //win.scrollIntoView(messages[i]);
                dojo.byId('contenido').scrollIntoView();
            }
        }
        // do not allow the first active block to have count = 1 and then request rotations
        if (valid) {
            for (i = 0; i < activations.length; ++i) {
                if (dojo.byId(activations[i]).checked && 1 == dijit.byId(counts[i]).value) {
                    break;
                }
            }
            for (++i; i < activations.length; ++i) {
                if (dojo.byId(activations[i]).checked) {
                    valid = false;
                    domClass.remove(rotationMsg, 'hide');
                    //win.scrollIntoView(rotationMsg);
                    dojo.byId('contenido').scrollIntoView();
                    break;
                }
            }
        }

        return valid;
    }
    onSubmitClick = function(evt) {
        var hourlyHoursInput, hourlyDaysOfMonthInput, hourlyDaysOfWeekInput, hourlyMonthsInput, hourlyCountInput,
        dailyHoursInput, dailyDaysOfMonthInput, dailyDaysOfWeekInput, dailyMonthsInput, dailyCountInput,
        weeklyHoursInput, weeklyDaysOfMonthInput, weeklyDaysOfWeekInput, weeklyMonthsInput, weeklyCountInput,
        monthlyHoursInput, monthlyDaysOfMonthInput, monthlyDaysOfWeekInput, monthlyMonthsInput, monthlyCountInput,
        yearlyHoursInput, yearlyDaysOfMonthInput, yearlyDaysOfWeekInput, yearlyMonthsInput, yearlyCountInput;
        dailyCountInput         = dojo.byId('Policy_dailyCount');
        dailyDaysOfMonthInput   = dojo.byId('Policy_dailyDaysOfMonth');
        dailyDaysOfWeekInput    = dojo.byId('Policy_dailyDaysOfWeek');
        dailyHoursInput         = dojo.byId('Policy_dailyHours');
        dailyMonthsInput        = dojo.byId('Policy_dailyMonths');
        hourlyCountInput        = dojo.byId('Policy_hourlyCount');
        hourlyDaysOfMonthInput  = dojo.byId('Policy_hourlyDaysOfMonth');
        hourlyDaysOfWeekInput   = dojo.byId('Policy_hourlyDaysOfWeek');
        hourlyHoursInput        = dojo.byId('Policy_hourlyHours');
        hourlyMonthsInput       = dojo.byId('Policy_hourlyMonths');
        monthlyCountInput       = dojo.byId('Policy_monthlyCount');
        monthlyDaysOfMonthInput = dojo.byId('Policy_monthlyDaysOfMonth');
        monthlyDaysOfWeekInput  = dojo.byId('Policy_monthlyDaysOfWeek');
        monthlyHoursInput       = dojo.byId('Policy_monthlyHours');
        monthlyMonthsInput      = dojo.byId('Policy_monthlyMonths');
        weeklyCountInput        = dojo.byId('Policy_weeklyCount');
        weeklyDaysOfMonthInput  = dojo.byId('Policy_weeklyDaysOfMonth');
        weeklyDaysOfWeekInput   = dojo.byId('Policy_weeklyDaysOfWeek');
        weeklyHoursInput        = dojo.byId('Policy_weeklyHours');
        weeklyMonthsInput       = dojo.byId('Policy_weeklyMonths');
        yearlyCountInput        = dojo.byId('Policy_yearlyCount');
        yearlyDaysOfMonthInput  = dojo.byId('Policy_yearlyDaysOfMonth');
        yearlyDaysOfWeekInput   = dojo.byId('Policy_yearlyDaysOfWeek');
        yearlyHoursInput        = dojo.byId('Policy_yearlyHours');
        yearlyMonthsInput       = dojo.byId('Policy_yearlyMonths');

        // validation
        if (!isFormValid()) {
            evt.preventDefault();
            return false;
        }

        // daily fields
        dailyCountInput.value       = dijit.byId('dailyCount').value;
        dailyDaysOfMonthInput.value = '';
        dailyDaysOfWeekInput.value  = dojo.query('input[id^=daily][type=checkbox]')
            .filter(
                function(item) {
                    return item.checked && item.id != 'daily-activation';
                })
            .map(
                function(item) {
                    return item.id.split('-')[1];
                })
            .map(
                function(weekday) {
                    var dayToIndex = {monday:1, tuesday:2, wednesday:3, thursday:4, friday:5, saturday:6, sunday:7};
                    return dayToIndex[weekday];
                }
            ).join('|');
        dailyHoursInput.value       = dateToHour(dijit.byId('dailyHour').value);
        dailyMonthsInput.value      = '';
        // hourly fields
        hourlyCountInput.value       = dijit.byId('hourlyCount').value;
        hourlyDaysOfMonthInput.value = '';
        hourlyDaysOfWeekInput.value  = dojo.query('input[id^=hourly][type=checkbox]')
            .filter(
                function(item) {
                    return item.checked;
                })
            .map(
                function(item) {
                    return item.id.split('-')[1];
                })
            .map(
                function(weekday) {
                    var dayToIndex = {monday:1, tuesday:2, wednesday:3, thursday:4, friday:5, saturday:6, sunday:7};
                    return dayToIndex[weekday];
                }
            ).join('|');
        hourlyHoursInput.value       = dojo.query('#hourlyHours input[type=hidden]').map(
            function(item){
                return item.value.replace(/.(.....).../, '$1');
            }).join('|');
        hourlyMonthsInput.value      = '';
        // monthly fields
        monthlyCountInput.value       = dijit.byId('monthlyCount').value;
        monthlyDaysOfMonthInput.value = isNaN(dijit.byId('dayOfMonth').value) ? '' : dijit.byId('dayOfMonth').value;
        monthlyDaysOfWeekInput.value  = '';
        monthlyHoursInput.value       = dateToHour(dijit.byId('monthlyHour').value);
        // weekly fields
        weeklyCountInput.value       = dijit.byId('weeklyCount').value;
        weeklyDaysOfMonthInput.value = '';
        weeklyDaysOfWeekInput.value  = dojo.query('input[id^=weekly][type=radio]')
            .filter(
                function(item) {
                    return item.checked;
                })
            .map(
                function(item) {
                    return englishWeekDayToIdx(item.id.split('-')[1]);
                }).join('|');
        weeklyHoursInput.value       = dateToHour(dijit.byId('weeklyHour').value);
        // yearly fields
        yearlyHoursInput.value       = dateToHour(dijit.byId('yearlyHour').value);
        yearlyCountInput.value       = dijit.byId('yearlyCount').value;
        yearlyDaysOfMonthInput.value = isNaN(dijit.byId('dayOfYear').value.getDate()) ? '' : dijit.byId('dayOfYear').value.getDate();
        yearlyDaysOfWeekInput.value  = '';
        //yearlyHoursInput.value       = dateToHour(dijit.byId('yearlyHour').value);
        yearlyMonthsInput.value      = isNaN(dijit.byId('dayOfYear').value.getMonth()) ? '' : (dijit.byId('dayOfYear').value.getMonth() + 1);

        return true;
    };

    ready(function() {
              initHourlyHours();
              initHourlyDays();
              initDailyCount();
              initDailyHour();
              initDailyDays();
              initWeeklyCount();
              initWeeklyDayOfWeek();
              initWeeklyHour();
              initMonthlyCount();
              initMonthlyDayOfMonth();
              initMonthlyHour();
              initYearlyCount();
              initYearlyDay();
              initYearlyHour();
              initSaveButton();
              initActivationCheckboxes();
          });
});
