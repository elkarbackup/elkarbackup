require(['dojo', 'dojo/dom-construct', 'dijit/form/TimeTextBox', 'dojo/store/Memory', 'dojo/string', 'dojo/ready'],
function(dojo, domConstruct, TimeTextBox, Memory, string, ready){
    var dateToHour, englishWeekDayToIdx, getHourlyHours, hourlyHoursDiv, hourlyHourWidgets, hourlyCountWidget, hourToDate, idxToEnglishWeekDay, initDailyDays, initDailyHour, initHourlyDays, initHourlyHours, initMonthlyCount, initMonthlyDayOfMoth, initMonthlyHour, initSaveButton, initWeeklyCount, initWeeklyDayOfWeek, initWeeklyHour, initYearlyCount, initYearlyDay, initYearlyHour, newTimeWidget, onChangeHourlyCount, onChangeHourlyDay, onChangeHourlyHour, onSubmitClick, updateDailyCount;
    hourlyHourWidgets = [];
    dateToHour = function (d) {
        return string.pad(d.getHours(), 2, '0') + ":" + string.pad(d.getMinutes(), 2, '0');
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
    updateDailyCount = function() {
        dijit.byId('dailyCount').set('value', dojo.query('input[id^=daily][type=checkbox]').filter(function(item){return item.checked;}).length);
    };
    initDailyHour = function() {
        var value, widget, store;
        widget = dijit.byId('dailyHour');
        if (widget.get('value')) {
            value = widget.get('value');
        } else if (dojo.byId('Policy_dailyHours').value) {
            value = hourToDate(dojo.byId('Policy_dailyHours').value);
        } else {
            value = '';
        }
        dojo.connect(widget, 'onChange',
                     function(){
                         dijit.byId('weeklyHour').set('value', this.value);
                     });
        widget.set('value', value);
    };
    initDailyDays = function() {
        dojo.forEach(dojo.byId('Policy_dailyDaysOfWeek').value.split('|'), 
                     function(dayIdx) {
                         dayIdx = Number(dayIdx);
                         if (dayIdx >= 1 && dayIdx <= 7) {
                             dojo.byId('daily-' + idxToEnglishWeekDay(dayIdx)).checked = true;
                         }
                     });
        dojo.query('input[id^=daily][type=checkbox]') .connect('onchange', updateDailyCount);
        dojo.query('input[id^=hourly][type=checkbox]').connect('onchange', updateDailyCount);
        updateDailyCount();
    };
    initHourlyDays = function() {
        var dailyHoursInput, dailyCountInput;
        dailyHoursInput = dojo.byId('Policy_dailyHours');
        dailyCountInput = dojo.byId('Policy_dailyCount');
        dojo.query('#hourlyDays input').connect('onchange', onChangeHourlyDay);
        dojo.forEach(dojo.byId('Policy_hourlyDaysOfWeek').value.split('|'), 
                     function(dayIdx) {
                         dayIdx = Number(dayIdx);
                         if (dayIdx >= 1 && dayIdx <= 7) {
                             dojo.setAttr(dojo.byId('hourly-' + idxToEnglishWeekDay(dayIdx)), 'checked', true);
                         }
                     });
    };
    initHourlyHours = function() {
        var hourlyCountInput;
        hourlyHoursDiv   = dojo.byId('hourlyHours'),
        hourlyCountInput = dojo.byId('Policy_hourlyCount');
        dojo.forEach(getHourlyHours(),
                     function(time, i){
                         var timeWidget;
                         timeWidget = newTimeWidget(time);
                         domConstruct.place(timeWidget.domNode, hourlyHoursDiv, 'last');
                         hourlyHourWidgets.push(timeWidget);
                     });
        hourlyCountWidget = dijit.byId('hourlyCount');
        dojo.connect(hourlyCountWidget, 'onChange', onChangeHourlyCount);
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
    initMonthlyDayOfMoth = function() {
        var value;
        value = Number(dojo.byId('Policy_monthlyDaysOfMonth').value);
        if (value >= 1 && value <= 31) {
            dijit.byId('dayOfMonth').set('value', value);            
        }
    };
    initMonthlyHour = function() {
        var value, widget, store;
        widget = dijit.byId('monthlyHour');
        if (widget.get('value')) {
            value = widget.get('value');
        } else if (dojo.byId('Policy_monthlyHours').value) {
            value = hourToDate(dojo.byId('Policy_monthlyHours').value);
        } else {
            value = null;
        }
        dojo.connect(widget, 'onChange',
                     function(){
                         dijit.byId('yearlyHour').set('value', this.value);
                     });
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
        dojo.connect(widget, 'onChange',
                     function(){
                         dijit.byId('monthlyHour').set('value', this.value);
                     });
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
     * Create a TimeTexBox with its value set to hour. Hour is a string of time "HH:mm", default "09:00"
     */
    newTimeWidget = function(hour) {
        return new TimeTextBox({value: hourToDate(hour),
                                onChange: onChangeHourlyHour,
                                constraints: {
                                    timePattern: 'HH:mm',
                                    clickableIncrement: 'T00:15:00',
                                    visibleIncrement: 'T00:15:00',
                                    visibleRange: 'T01:00:00'
                                }});
    };
    onChangeHourlyDay = function(value) {
        // set/unset the daily day when the corresponding houly day changes
        dojo.setAttr(dojo.byId('daily-' + this.id.split('-')[1]), 'checked', this.checked);
    };
    onChangeHourlyCount = function(value) {
        var i, aTime, hours, timeWidget;
        hours = [];
        for (i = hourlyHourWidgets.length - 1; i > value - 1; --i) {
            hourlyHourWidgets.pop().destroy();
        }
        for (i = hourlyHourWidgets.length - 1; i < value - 1; ++i) {
            timeWidget = newTimeWidget();
            domConstruct.place(timeWidget.domNode, hourlyHoursDiv, 'last');
            hourlyHourWidgets.push(timeWidget);
        }
    };
    onChangeHourlyHour = function(value) {
    };
    onSubmitClick = function(evt) {
        var hourlyHoursInput, hourlyDaysOfMonthInput, hourlyDaysOfWeekInput, hourlyMonthsInput, hourlyCountInput,
        dailyHoursInput, dailyDaysOfMonthInput, dailyDaysOfWeekInput, dailyMonthsInput, dailyCountInput,
        weeklyHoursInput, weeklyDaysOfMonthInput, weeklyDaysOfWeekInput, weeklyMonthsInput, weeklyCountInput,
        monthlyHoursInput, monthlyDaysOfMonthInput, monthlyDaysOfWeekInput, monthlyMonthsInput, monthlyCountInput,
        yearlyHoursInput, yearlyDaysOfMonthInput, yearlyDaysOfWeekInput, yearlyMonthsInput, yearlyCountInput;
        // dojo.stopEvent(evt);
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

        // daily fields
        dailyCountInput.value       = dojo.query('input[id^=daily][type=checkbox]').filter(function(item){return item.checked;}).length;
        dailyDaysOfMonthInput.value = '';
        dailyDaysOfWeekInput.value  = dojo.query('input[id^=daily][type=checkbox]')
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
        hourlyHoursInput.value       = dojo.map(hourlyHourWidgets,
            function(item){
                return dateToHour(item.value);
            }).join('|');
        hourlyMonthsInput.value      = '';
        // monthly fields
        monthlyCountInput.value       = dijit.byId('monthlyCount').value;
        monthlyDaysOfMonthInput.value = dijit.byId('dayOfMonth').value;
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
        yearlyDaysOfMonthInput.value = dijit.byId('dayOfYear').value.getDate();
        yearlyDaysOfWeekInput.value  = '';
        yearlyHoursInput.value       = dateToHour(dijit.byId('yearlyHour').value);
        yearlyMonthsInput.value      = dijit.byId('dayOfYear').value.getMonth() + 1;
        return true;
    };

    ready(function() {
              initHourlyHours();
              initHourlyDays();
              initDailyHour();
              initDailyDays();
              initWeeklyCount();
              initWeeklyDayOfWeek();
              initWeeklyHour();
              initMonthlyCount();
              initMonthlyDayOfMoth();
              initMonthlyHour();
              initYearlyCount();
              initYearlyDay();
              initYearlyHour();
              initSaveButton();
          });
});
