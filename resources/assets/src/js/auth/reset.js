'use strict';

$('#reset-button').click(e => {
    e.preventDefault();

    const data = {
        password: $('#password').val(),
    };

    (function validate({ password }, callback) {
        if (password === '') {
            showMsg(trans('auth.emptyPassword'));
            $('#password').focus();
        } else if (password.length < 8 || password.length > 16) {
            showMsg(trans('auth.invalidPassword'), 'warning');
            $('#password').focus();
        } else if ($('#confirm-pwd').val() === '') {
            showMsg(trans('auth.emptyConfirmPwd'));
            $('#confirm-pwd').focus();
        } else if (password !== $('#confirm-pwd').val()) {
            showMsg(trans('auth.invalidConfirmPwd'), 'warning');
            $('#confirm-pwd').focus();
        } else {
            callback();
        }
    })(data, async () => {
        try {
            const { errno, msg } = await fetch({
                type: 'POST',
                url: `${url('auth/reset')}/${$('#uid').val()}?expires=${getQueryString('expires')}&signature=${getQueryString('signature')}`,
                dataType: 'json',
                data,
                beforeSend: () => {
                    $('#reset-button').html(
                        '<i class="fa fa-spinner fa-spin"></i> ' + trans('auth.resetting')
                    ).prop('disabled', 'disabled');
                }
            });
            if (errno === 0) {
                swal({
                    type: 'success',
                    html: msg
                }).then(() => (window.location = url('auth/login')));
            } else {
                showMsg(msg, 'warning');
                $('#reset-button').html(trans('auth.reset')).prop('disabled', '');
            }
        } catch (error) {
            showAjaxError(error);
            $('#reset-button').html(trans('auth.reset')).prop('disabled', '');
        }
    });
});
