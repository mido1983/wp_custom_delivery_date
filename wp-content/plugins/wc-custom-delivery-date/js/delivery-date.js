jQuery(document).ready(function($) {
    console.log('Delivery date picker script loaded');
    console.log('wc_cdd_params:', wc_cdd_params);
    
    function initDatepicker() {
        console.log('Initializing datepicker');
        var $deliveryDateField = $('#delivery_date');
        console.log('Delivery date field found:', $deliveryDateField.length > 0);
        if ($deliveryDateField.length === 0) {
            console.error('Delivery date field not found');
            return;
        }
        
        try {
            $deliveryDateField.datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: parseInt(wc_cdd_params.min_date),
                maxDate: parseInt(wc_cdd_params.max_date),
                beforeShowDay: disableDates,
                onSelect: function(dateText) {
                    console.log('Date selected:', dateText);
                }
            });
            console.log('Datepicker initialized successfully');
        } catch (error) {
            console.error('Error initializing datepicker:', error);
        }
    }

    function disableDates(date) {
        var day = date.getDay();
        var dateString = $.datepicker.formatDate('yy-mm-dd', date);
        
        // Check if the date is in excluded dates
        if ($.inArray(dateString, wc_cdd_params.excluded_dates) !== -1) {
            return [false];
        }
        
        // Check if the day is in allowed days
        if ($.inArray(day.toString(), wc_cdd_params.allowed_days) === -1) {
            return [false];
        }
        
        // Check delivery hours
        var currentTime = new Date();
        if (date.getDate() === currentTime.getDate() && 
            date.getMonth() === currentTime.getMonth() && 
            date.getFullYear() === currentTime.getFullYear()) {
            var currentHour = currentTime.getHours();
            var endHour = parseInt(wc_cdd_params.delivery_hours.end.split(':')[0]);
            if (currentHour >= endHour) {
                return [false];
            }
        }
        
        return [true];
    }

    initDatepicker();

    // Re-init datepicker when checkout is updated
    $(document.body).on('updated_checkout', function() {
        initDatepicker();
    });

    // Добавьте эту строку в конец файла
    console.log('All form fields:', $('form.checkout .form-row').length);
});