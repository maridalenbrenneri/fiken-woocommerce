jQuery(document).ready(function () {
    jQuery("#filter_date_start").datepicker({dateFormat: "yy-mm-dd"});
    jQuery("#filter_date_end").datepicker({dateFormat: "yy-mm-dd"});


    jQuery('#img_filter_date_start').on('click', function () {
        jQuery("#filter_date_start").datepicker('show');
    })

    jQuery('#img_filter_date_end').on('click', function () {
        jQuery("#filter_date_end").datepicker('show');
    })


    jQuery('.close').click(function () {
        jQuery(this).parent('div').hide();
    })


    jQuery('.tab-header').on('click', function () {
        jQuery(".tab-header").removeClass('active');
        jQuery(".tab-pane").removeClass('active');
        jQuery(this).addClass('active');
        jQuery('#' + jQuery(this).data('content-tab-id')).addClass('active');
    })


    jQuery('#fiken_company').change(function () {
        jQuery('#form_settings').submit();
    })

    jQuery('#cmdRefresh').click(function () {
        jQuery('#form_settings').submit();
    });

    jQuery('.settings_sel').click(function () {
        jQuery(this).css('color', '#555555');
    });

    jQuery('.settings_sel').focusout(function () {
        setSelectColor();
    })


    jQuery('.reg_order').click(function () {
        var orderId = jQuery(this).data('order-id');
        if (jQuery('#order_id_' + orderId + ' .txtStatus').data('status-id') == '2') {
            jConfirm(admin_fiken_confirm_reg_if_transferred.replace("%s", jQuery('#order_id_' + orderId + ' .txtStatus').text()), 'orderId', function (res) {
                if (res) {
                    regOrder(orderId);
                }
            });
        } else {
            regOrder(orderId);
        }
    });


    jQuery('.view_status_history').click(function () {
        var orderId = jQuery(this).data('order-id')
        if (jQuery('tr#status_history_' + orderId + ':visible').length) {
            jQuery('tr#status_history_' + orderId).fadeOut('slow');
        }
        else {
            view_status_history(orderId);
        }
    });


    jQuery('#sel_order_all').click(function () {
        markControl(jQuery(this), -1);
        markControlMass(parseInt(jQuery(this).data('check')));
    });

    jQuery('.sel_order').click(function () {
        markControl(jQuery(this), -1);
    });

    jQuery('#reg_order_mass').click(function () {
        regOrderMass();
    });

    jQuery('li[data-content-tab-id="' + admin_fiken_current_tab + '"]').click();
    setSelectColor();

    jQuery('.fiken_sale_kind').change(function () {
        setEmptyAccount(jQuery(this));
    })

});

function setEmptyAccount(ctrlCurrentSaleKind) {
        var ctrlSelectAccount = ctrlCurrentSaleKind.closest('tr').find('.fiken_account');
        if(ctrlCurrentSaleKind.val() == 'EXTERNAL_INVOICE' ) {
            ctrlSelectAccount.find("option[value='0']").show();
        }else{
            if (ctrlSelectAccount.find("option:selected").val() == "0"){
                ctrlSelectAccount.prepend( jQuery('<option value="" style="display:none;" selected="selected" >' + admin_fiken_select_account + '</option>'))
            };
            ctrlSelectAccount.find("option[value='0']").hide();
        }
    setSelectColor();
}

function setSelectColor() {
    jQuery('.settings_sel').each(function () {
        if (jQuery(this).val() == '') {
            jQuery(this).css('color', 'red');
        }else{
            jQuery(this).css('color', 'inherit');
        }
    })
}

function showAlert(type, mes) {
    jQuery('.alert-danger').hide('fast');
    jQuery('.alert-success').hide('fast');
    switch (type) {
        case 0 :
            jQuery('#alert-success').html(mes);
            jQuery('.alert-success').show('slow');
            break;
        case 1 :
            jQuery('#alert-danger').html(mes);
            jQuery('.alert-danger').show('slow');
            break;
    }
}


function markControlMass(type) {
    jQuery('.sel_order').each(function () {
        markControl(jQuery(this), type)
    })
}

//type = 0: uncheck  1:check  -1:revent
function markControl(ctrl, type) {
    switch (type) {
        case 0:
            ctrl.data('check', '0');
            ctrl.removeClass('check');
            ctrl.addClass('check_empty');
            jQuery('#order_id_' + ctrl.data('order-id')).css('background-color', '');
            break;
        case 1:
            ctrl.data('check', '1');
            ctrl.removeClass('check_empty');
            ctrl.addClass('check');
            jQuery('#order_id_' + ctrl.data('order-id')).css('background-color', '#E6FFE6');
            break;
        case -1:
            if (ctrl.data('check') == '0') {
                markControl(ctrl, 1);
            } else {
                markControl(ctrl, 0);
            }
            break;
    }
}


function filter() {
    var url = admin_fiken_base_url;
    var filter_date_start = jQuery('#filter_date_start').val();

    if (filter_date_start) {
        url += '&filter_date_start=' + encodeURIComponent(filter_date_start);
    }
    var filter_date_end = jQuery('#filter_date_end').val();
    if (filter_date_end) {
        url += '&filter_date_end=' + encodeURIComponent(filter_date_end);
    }
    var filter_order_status_id = jQuery('#filter_order_status_id').val();
    if (filter_order_status_id) {
        url += '&filter_order_status_id=' + encodeURIComponent(filter_order_status_id);
    }
    location = url;
}


function regOrder(orderId) {
    jQuery('.alert-danger').hide('fast');
    jQuery('.alert-success').hide('fast');
    jQuery.ajax({
        url: admin_fiken_base_url_ajax,
        type: 'POST',
        dataType: 'json',
        async: true,
        cache: false,
        data: {
            ajax: true,
            action: 'fiken_register_sale',
            order_id: orderId
        },
        beforeSend: function () {
            jQuery('#order_id_' + orderId + ' .reg_order').addClass('ajax-loader');
            jQuery('#order_id_' + orderId + ' .reg_order').attr('disabled', 'disabled');
        },
        complete: function () {
            jQuery('#order_id_' + orderId + ' .reg_order').removeClass('ajax-loader');
            jQuery('#order_id_' + orderId + ' .reg_order').removeAttr('disabled');
        },
        error: function (xhr, ajaxOptions, thrownError) {
            jAlert("TECHNICAL ERROR:\n\nDetails: " + ajaxOptions, 'Error');
        },
        success: function (data) {
            if (data == undefined) {
                jAlert("TECHNICAL ERROR: data is null", 'Error');
                return;
            }
            if (data.error) {
                showAlert(1, data.error);
            }
            else {
                showAlert(0, data.success);
            }

            jQuery('#order_id_' + orderId + ' .txtStatus').text(data.order.stateName);
            jQuery('#order_id_' + orderId + ' .txtStatus').data('status-id', data.order.currentState);

            switch (data.order.currentState) {
                case '2' :
                    jQuery('#order_id_' + orderId).css('color', 'green');
                    break;
                case '3' :
                    jQuery('#order_id_' + orderId).css('color', 'red');
                    break;
                default :
                    jQuery('#order_id_' + orderId).css('color', '');
            }
        }
    });
}


function regOrderMass() {
    jQuery('.alert-danger').hide('fast');
    jQuery('.alert-success').hide('fast');

    var arrIds = [];
    jQuery('.sel_order').each(function () {
        if (jQuery(this).data('check') == '1') {
            arrIds[arrIds.length] = jQuery(this).data('order-id') + '';
        }
    })

    if (!arrIds.length) {
        showAlert(1, admin_fiken_err_nothing_selected);
        return;
    }

    var errMes = '';

    promise = jQuery.when();
    jQuery('.ajax-loader').show();
    jQuery.each(arrIds, function (index, orderId) {
        promise = promise.always(
            function () {
                return jQuery.ajax({
                    url: admin_fiken_base_url_ajax,
                    type: 'POST',
                    dataType: 'json',
                    async: false,
                    cache: false,
                    data: {
                        ajax: true,
                        action: 'fiken_register_sale',
                        order_id: orderId
                    },
                    beforeSend: function () {
                        jQuery('#order_id_' + orderId + ' .reg_order').addClass('ajax-loader');
                        jQuery('#order_id_' + orderId + ' .reg_order').attr('disabled', 'disabled');
                    },
                    complete: function () {
                        jQuery('#order_id_' + orderId + ' .reg_order').removeClass('ajax-loader');
                        jQuery('#order_id_' + orderId + ' .reg_order').removeAttr('disabled');
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        errMes += '<li> ID: ' + orderId + ' : TECHNICAL ERROR. Details: ' + ajaxOptions + '</li>';
                    },
                    success: function (data) {
                        if (data == undefined) {
                            errMes += '<li> ID: ' + orderId + ' : TECHNICAL ERROR: data is null </li>';
                        } else {
                            if (data.error) {
                                errMes += '<li>ID: ' + orderId + ' : ' + data.error + '</li>';
                            }

                            jQuery('#order_id_' + orderId + ' .txtStatus').text(data.order.stateName);
                            jQuery('#order_id_' + orderId + ' .txtStatus').data('status-id', data.order.currentState);

                            switch (data.order.currentState) {
                                case '2' :
                                    jQuery('#order_id_' + orderId).css('color', 'green');
                                    break;
                                case '3' :
                                    jQuery('#order_id_' + orderId).css('color', 'red');
                                    break;
                                default :
                                    jQuery('#order_id_' + orderId).css('color', '');
                            }
                        }
                    }
                });

            });

    });

    promise.always(function () {
        jQuery('.ajax-loader').hide();
        if (errMes != '') {
            showAlert(1, '<ul>' + errMes + '</ul>')
        } else {
            showAlert(0, admin_fiken_order_register_success_mass);
        }
    });
}


function view_status_history(orderId) {
    jQuery.ajax({
        url: admin_fiken_base_url_ajax,
        type: 'POST',
        dataType: 'json',
        async: true,
        cache: false,
        data: {
            ajax: true,
            action: 'fiken_get_order_status_history',
            order_id: orderId
        },
        beforeSend: function () {
            jQuery('#order_id_' + orderId + ' .view_status_history').addClass('ajax-loader');
            jQuery('#order_id_' + orderId + ' .view_status_history').attr('disabled', 'disabled');
        },
        complete: function () {
            jQuery('#order_id_' + orderId + ' .view_status_history').removeClass('ajax-loader');
            jQuery('#order_id_' + orderId + ' .view_status_history').removeAttr('disabled');
        },
        error: function (xhr, ajaxOptions, thrownError) {
            jAlert("TECHNICAL ERROR:\n\nDetails: " + ajaxOptions, 'Error');
        },
        success: function (data) {
            if (data == undefined) {
                jAlert("TECHNICAL ERROR: data is null", 'Error');
                return;
            }

            if (data.error) {
                showAlert(1, data.error);
            }
            else {
                jQuery('tr#status_history_' + orderId).find('table > tbody').html('');
                if (data && data.length) {
                    var newRows = '';
                    data.forEach(function (item, i, arr) {
                        //set row color
                        var color = ''
                        switch (item.currentState) {
                            case '2' :
                                color = 'style = "color:green"'
                                break;
                            case '3' :
                                color = 'style = "color:red"'
                                break;
                        }
                        newRows += '<tr ' + color + '><td class="text-left">' + item.stateName + '</td>' +
                        '<td class="text-left">' + item.lastUpdate + '</td>' +
                        '<td class="text-left">' + (item.mes != null ? item.mes : '') + '</td></tr>';
                    });
                    jQuery('tr#status_history_' + orderId).find('table > tbody').html(newRows);
                    jQuery('tr#status_history_' + orderId).fadeIn('slow');
                }
            }
        }
    });
}
